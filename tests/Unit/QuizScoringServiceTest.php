<?php

namespace Tests\Unit;

use App\Models\KnowledgeDomain;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Library\DomainRankingService;
use App\Services\Library\QuizScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class QuizScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_scores_single_and_multiple_choice_answers_and_updates_best_score(): void
    {
        $user = User::factory()->professional()->create();
        $domain = $this->domain();
        $quiz = $this->quiz($domain, ['max_attempts_per_user' => 2]);
        $single = $this->question($quiz, ['correct_answer' => '1', 'points' => 2]);
        $multi = $this->question($quiz, ['question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE, 'correct_answer' => ['0', '2'], 'points' => 3, 'sort_order' => 2]);
        $service = new QuizScoringService(new DomainRankingService);

        $attempt = $service->submitQuiz($quiz, $user, [
            (string) $single->id => '1',
            (string) $multi->id => ['2', '0'],
        ]);

        $this->assertSame(5, $attempt->score);
        $this->assertSame(5, $attempt->max_possible_score);
        $this->assertDatabaseHas('user_quiz_best_scores', ['user_id' => $user->id, 'quiz_id' => $quiz->id, 'best_score' => 5]);
        $this->assertDatabaseHas('user_domain_points', ['user_id' => $user->id, 'knowledge_domain_id' => $domain->id, 'total_points' => 5]);
    }

    public function test_rejects_unpublished_quizzes_max_attempts_and_completed_resubmits(): void
    {
        $user = User::factory()->professional()->create();
        $domain = $this->domain();
        $draft = $this->quiz($domain, ['status' => Quiz::STATUS_DRAFT]);
        $quiz = $this->quiz($domain, ['max_attempts_per_user' => 1]);
        $question = $this->question($quiz);
        $service = new QuizScoringService(new DomainRankingService);

        $this->expectException(InvalidArgumentException::class);
        $service->startAttempt($draft, $user);
    }

    public function test_max_attempt_and_completed_attempt_errors_are_reported(): void
    {
        $user = User::factory()->professional()->create();
        $quiz = $this->quiz($this->domain(), ['max_attempts_per_user' => 1]);
        $question = $this->question($quiz);
        $service = new QuizScoringService(new DomainRankingService);
        $attempt = $service->submitQuiz($quiz, $user, [$question->id => 0]);

        try {
            $service->startAttempt($quiz, $user);
            $this->fail('Expected max attempts exception.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Maximum attempts reached for this quiz.', $exception->getMessage());
        }

        try {
            $service->submitAttempt($attempt, [$question->id => 0]);
            $this->fail('Expected completed attempt exception.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Completed quiz attempts cannot be submitted again.', $exception->getMessage());
        }
    }

    private function domain(array $attributes = []): KnowledgeDomain
    {
        return KnowledgeDomain::query()->create(array_merge(['name' => 'Process Safety', 'slug' => 'process-safety', 'status' => KnowledgeDomain::STATUS_ACTIVE], $attributes));
    }

    private function quiz(KnowledgeDomain $domain, array $attributes = []): Quiz
    {
        return Quiz::query()->create(array_merge(['knowledge_domain_id' => $domain->id, 'title' => 'Safety Quiz', 'slug' => 'safety-quiz-'.uniqid(), 'status' => Quiz::STATUS_PUBLISHED], $attributes));
    }

    private function question(Quiz $quiz, array $attributes = []): QuizQuestion
    {
        return QuizQuestion::query()->create(array_merge(['quiz_id' => $quiz->id, 'question_text' => 'Pick one', 'question_type' => QuizQuestion::TYPE_SINGLE_CHOICE, 'options' => ['A', 'B', 'C'], 'correct_answer' => '0', 'points' => 1, 'sort_order' => 1], $attributes));
    }
}
