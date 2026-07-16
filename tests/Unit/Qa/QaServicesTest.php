<?php

namespace Tests\Unit\Qa;

use App\Models\Answer;
use App\Models\KnowledgeDomain;
use App\Models\LeaderboardSetting;
use App\Models\PointTransaction;
use App\Models\Question;
use App\Models\ReputationRankTier;
use App\Models\User;
use App\Services\Qa\AnswerModerationService;
use App\Services\Qa\LeaderboardSnapshotService;
use App\Services\Qa\QuestionWorkflowService;
use App\Services\Qa\ReputationLedgerService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class QaServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_workflow_creates_question_with_defaults(): void
    {
        $user = User::factory()->create();
        $question = (new QuestionWorkflowService())->createQuestion([
            'user_id' => $user->id,
            'title' => 'How often should basil be watered?',
            'body' => 'Need advice for a balcony container.',
        ]);

        $this->assertSame('pending', $question->status, 'Questions should default to pending.');
        $this->assertFalse($question->is_anonymous, 'Questions should default to non-anonymous.');
    }

    public function test_question_workflow_requires_admin_for_partner_proxy_posting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('posted_by_admin_id is required when posting on behalf of a partner.');

        (new QuestionWorkflowService())->createQuestion([
            'user_id' => User::factory()->create()->id,
            'on_behalf_of_partner_id' => 1,
            'title' => 'Proxy question',
            'body' => 'Partner proxy posts require an admin.',
        ]);
    }

    public function test_question_workflow_publishes_and_deduplicates_domain_links(): void
    {
        $question = Question::factory()->create(['status' => 'pending']);
        $domains = KnowledgeDomain::factory()->count(2)->create();
        $service = new QuestionWorkflowService();

        $published = $service->publish($question);
        $service->linkKnowledgeDomains($published, [$domains[0]->id, $domains[0]->id, $domains[1]->id]);

        $this->assertSame('published', $published->status, 'Publish should move question to published status.');
        $this->assertSame(2, $published->knowledgeDomains()->count(), 'Domain links should be unique after sync.');
    }

    public function test_answer_moderation_features_and_unfeatures_answer(): void
    {
        $answer = Answer::factory()->create();
        $service = new AnswerModerationService();

        $featured = $service->feature($answer, 'high', 1);

        $this->assertTrue($featured->is_admin_featured, 'Feature should mark answer as admin featured.');
        $this->assertSame('high', $featured->confidence_level, 'Feature should store confidence level.');

        $unfeatured = $service->unfeature($featured);
        $this->assertFalse($unfeatured->is_admin_featured, 'Unfeature should clear admin featured state.');
        $this->assertNull($unfeatured->confidence_level, 'Unfeature should clear confidence level.');
        $this->assertNull($unfeatured->admin_rank_order, 'Unfeature should clear rank order.');
    }

    public function test_answer_moderation_rejects_invalid_confidence_level(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('confidence_level must be low, medium, high, or null.');

        (new AnswerModerationService())->feature(Answer::factory()->create(), 'certain');
    }

    public function test_answer_moderation_rejects_reordering_answer_from_another_question(): void
    {
        $question = Question::factory()->create();
        $otherAnswer = Answer::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Answer {$otherAnswer->id} does not belong to the question.");

        (new AnswerModerationService())->reorderFeaturedAnswers($question, [$otherAnswer->id => 1]);
    }

    public function test_reputation_ledger_records_points_and_recalculates_rank(): void
    {
        $user = User::factory()->create();
        ReputationRankTier::factory()->create(['star_level' => 1, 'min_points' => 0]);
        ReputationRankTier::factory()->create(['star_level' => 3, 'min_points' => 500]);
        $service = new ReputationLedgerService();

        $transaction = $service->recordQuestionPoints($user->id, 44, 600);
        $reputation = $service->recalculateUserReputation($user->id);

        $this->assertSame('question', $transaction->source_type, 'Question points should use question source type.');
        $this->assertSame(600, $reputation->total_points, 'Reputation should cache summed point transactions.');
        $this->assertSame(3, $reputation->current_star_rank, 'Reputation should resolve the highest matching rank tier.');
    }

    public function test_reputation_ledger_rejects_empty_manual_reason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reason is required for manual reputation adjustments.');

        (new ReputationLedgerService())->recordManualAdjustment(User::factory()->create()->id, 10, '   ', User::factory()->create()->id);
    }

    public function test_leaderboard_snapshot_applies_threshold_top_n_and_replaces_existing_month(): void
    {
        CarbonImmutable::setTestNow('2026-07-16 12:00:00');
        LeaderboardSetting::factory()->create([
            'min_points_threshold' => 100,
            'top_n' => 2,
            'effective_from' => '2026-01-01',
        ]);
        $users = User::factory()->count(3)->create();
        PointTransaction::factory()->create(['user_id' => $users[0]->id, 'points' => 200, 'created_at' => '2026-07-02 10:00:00']);
        PointTransaction::factory()->create(['user_id' => $users[1]->id, 'points' => 150, 'created_at' => '2026-07-03 10:00:00']);
        PointTransaction::factory()->create(['user_id' => $users[2]->id, 'points' => 90, 'created_at' => '2026-07-04 10:00:00']);

        $snapshots = (new LeaderboardSnapshotService())->createMonthlySnapshot('2026-07');

        $this->assertCount(2, $snapshots, 'Snapshot should include only top users meeting threshold.');
        $this->assertSame($users[0]->id, $snapshots[0]->user_id, 'Highest monthly points should rank first.');
        $this->assertSame(1, $snapshots[0]->rank_position, 'First snapshot row should have rank one.');
    }

    public function test_leaderboard_snapshot_requires_effective_setting(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No leaderboard setting is effective for the requested month.');

        (new LeaderboardSnapshotService())->createMonthlySnapshot('2026-07');
    }
}
