<?php

namespace Tests\Unit;

use App\Models\DomainRankTier;
use App\Models\KnowledgeDomain;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserQuizBestScore;
use App\Services\Library\DomainRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainRankingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_best_score_without_downgrading_and_recalculates_domain_rank(): void
    {
        $user = User::factory()->professional()->create();
        $domain = $this->domain();
        $bronze = $this->tier($domain, ['name' => 'Bronze', 'min_points' => 1, 'sort_order' => 1]);
        $gold = $this->tier($domain, ['name' => 'Gold', 'min_points' => 8, 'sort_order' => 2]);
        $quiz = $this->quiz($domain);
        $service = new DomainRankingService;

        $first = $this->attempt($quiz, $user, 10, 1);
        $service->updateBestScoreFromAttempt($first);
        $lower = $this->attempt($quiz, $user, 4, 2);
        $bestScore = $service->updateBestScoreFromAttempt($lower);

        $this->assertSame(10, $bestScore->best_score);
        $this->assertSame($first->id, $bestScore->best_quiz_attempt_id);
        $this->assertDatabaseHas('user_domain_points', ['user_id' => $user->id, 'knowledge_domain_id' => $domain->id, 'total_points' => 10, 'current_rank_tier_id' => $gold->id]);
    }

    public function test_recalculates_points_across_multiple_quizzes_in_domain(): void
    {
        $user = User::factory()->professional()->create();
        $domain = $this->domain();
        $tier = $this->tier($domain, ['min_points' => 5]);
        $quizA = $this->quiz($domain, ['slug' => 'quiz-a']);
        $quizB = $this->quiz($domain, ['slug' => 'quiz-b']);
        UserQuizBestScore::query()->create(['user_id' => $user->id, 'quiz_id' => $quizA->id, 'best_score' => 3, 'achieved_at' => now()]);
        UserQuizBestScore::query()->create(['user_id' => $user->id, 'quiz_id' => $quizB->id, 'best_score' => 4, 'achieved_at' => now()]);

        $point = (new DomainRankingService)->recalculateUserDomainPoints($user->id, $domain);

        $this->assertSame(7, $point->total_points);
        $this->assertSame($tier->id, $point->current_rank_tier_id);
    }

    private function domain(): KnowledgeDomain
    {
        return KnowledgeDomain::query()->create(['name' => 'Operations', 'slug' => 'operations', 'status' => KnowledgeDomain::STATUS_ACTIVE]);
    }

    private function tier(KnowledgeDomain $domain, array $attributes = []): DomainRankTier
    {
        return DomainRankTier::query()->create(array_merge(['knowledge_domain_id' => $domain->id, 'name' => 'Tier', 'min_points' => 0, 'sort_order' => 1], $attributes));
    }

    private function quiz(KnowledgeDomain $domain, array $attributes = []): Quiz
    {
        return Quiz::query()->create(array_merge(['knowledge_domain_id' => $domain->id, 'title' => 'Quiz', 'slug' => 'quiz-'.uniqid(), 'status' => Quiz::STATUS_PUBLISHED], $attributes));
    }

    private function attempt(Quiz $quiz, User $user, int $score, int $number): QuizAttempt
    {
        return QuizAttempt::query()->create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'attempt_number' => $number, 'answers_submitted' => [], 'score' => $score, 'max_possible_score' => 10, 'started_at' => now(), 'completed_at' => now()]);
    }
}
