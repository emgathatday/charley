<?php

namespace Tests\Unit\Qa;

use App\Models\Answer;
use App\Models\KnowledgeDomain;
use App\Models\LeaderboardSetting;
use App\Models\Question;
use App\Models\ReputationRankTier;
use App\Models\User;
use App\Models\UserReputation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_casts_attachment_ids_and_anonymous_flag(): void
    {
        $question = Question::factory()->create([
            'is_anonymous' => 1,
            'attachment_media_ids' => [10, 11],
        ]);

        $this->assertTrue($question->is_anonymous, 'Question anonymity should be cast to boolean.');
        $this->assertSame([10, 11], $question->attachment_media_ids, 'Question attachment IDs should be cast to array.');
    }

    public function test_question_has_answers_and_cascades_delete(): void
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->create(['question_id' => $question->id]);

        $this->assertTrue($question->answers->contains($answer), 'Question should expose related answers.');

        $question->delete();

        $this->assertDatabaseMissing('answers', ['id' => $answer->id]);
    }

    public function test_question_domain_links_enforce_unique_pair(): void
    {
        $question = Question::factory()->create();
        $domain = KnowledgeDomain::factory()->create();

        $question->knowledgeDomains()->attach($domain->id);

        $this->expectException(QueryException::class);

        $question->knowledgeDomains()->attach($domain->id);
    }

    public function test_question_rejects_invalid_user_foreign_key(): void
    {
        $this->expectException(QueryException::class);

        Question::query()->create([
            'user_id' => 999999,
            'title' => 'Invalid owner',
            'body' => 'Foreign key should reject missing user.',
            'status' => 'pending',
        ]);
    }

    public function test_user_reputation_resolves_current_rank_tier(): void
    {
        $user = User::factory()->create();
        $tier = ReputationRankTier::factory()->create([
            'star_level' => 3,
            'min_points' => 750,
            'label' => 'Trusted grower',
        ]);
        $reputation = UserReputation::factory()->create([
            'user_id' => $user->id,
            'total_points' => 900,
            'current_star_rank' => 3,
        ]);

        $this->assertTrue($reputation->currentRankTier->is($tier), 'User reputation should resolve rank tier by star level.');
    }

    public function test_leaderboard_setting_casts_numeric_thresholds_and_effective_date(): void
    {
        $setting = LeaderboardSetting::factory()->create([
            'min_points_threshold' => '100',
            'top_n' => '5',
            'effective_from' => '2026-07-01',
        ]);

        $this->assertSame(100, $setting->min_points_threshold, 'Leaderboard threshold should cast to integer.');
        $this->assertSame(5, $setting->top_n, 'Leaderboard top_n should cast to integer.');
        $this->assertSame('2026-07-01', $setting->effective_from->toDateString(), 'Leaderboard effective_from should cast to date.');
    }
}
