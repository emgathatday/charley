<?php

namespace App\Services\Library;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuizScoringService
{
    public function __construct(
        private readonly DomainRankingService $domainRankingService,
    ) {
    }

    public function startAttempt(Quiz $quiz, User|int $user): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $user): QuizAttempt {
            $userId = $user instanceof User ? $user->id : $user;
            $quiz->loadMissing('questions');

            if (! $quiz->isPublished()) {
                throw new InvalidArgumentException('Only published quizzes can be attempted.');
            }

            $attemptCount = QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->get(['id'])
                ->count();

            if ($quiz->max_attempts_per_user !== null && $attemptCount >= $quiz->max_attempts_per_user) {
                throw new InvalidArgumentException('Maximum attempts reached for this quiz.');
            }

            return QuizAttempt::query()->create([
                'quiz_id' => $quiz->id,
                'user_id' => $userId,
                'attempt_number' => $attemptCount + 1,
                'answers_submitted' => null,
                'score' => 0,
                'max_possible_score' => $this->maxPossibleScore($quiz),
                'started_at' => now(),
                'completed_at' => null,
            ]);
        });
    }

    public function submitQuiz(Quiz $quiz, User|int $user, array $answers): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $user, $answers): QuizAttempt {
            $attempt = $this->startAttempt($quiz, $user);

            return $this->submitAttempt($attempt, $answers);
        });
    }

    public function submitAttempt(QuizAttempt $quizAttempt, array $answers): QuizAttempt
    {
        return DB::transaction(function () use ($quizAttempt, $answers): QuizAttempt {
            $quizAttempt->loadMissing('quiz.questions');

            if ($quizAttempt->completed_at !== null) {
                throw new InvalidArgumentException('Completed quiz attempts cannot be submitted again.');
            }

            $score = $this->calculateScore($quizAttempt->quiz, $answers);
            $maxPossibleScore = $quizAttempt->max_possible_score > 0
                ? $quizAttempt->max_possible_score
                : $this->maxPossibleScore($quizAttempt->quiz);

            $quizAttempt->update([
                'answers_submitted' => $answers,
                'score' => $score,
                'max_possible_score' => $maxPossibleScore,
                'completed_at' => now(),
            ]);

            $this->domainRankingService->updateBestScoreFromAttempt($quizAttempt->refresh());

            return $quizAttempt->load(['quiz.knowledgeDomain', 'bestScore']);
        });
    }

    public function calculateScore(Quiz $quiz, array $answers): int
    {
        $quiz->loadMissing('questions');

        return (int) $quiz->questions->sum(function (QuizQuestion $question) use ($answers): int {
            $submittedAnswer = $this->answerForQuestion($answers, $question);

            if ($submittedAnswer === null) {
                return 0;
            }

            return $this->answerIsCorrect($question, $submittedAnswer) ? $question->points : 0;
        });
    }

    public function maxPossibleScore(Quiz $quiz): int
    {
        $quiz->loadMissing('questions');

        return (int) $quiz->questions->sum('points');
    }

    public function answerIsCorrect(QuizQuestion $question, mixed $submittedAnswer): bool
    {
        $correctAnswer = $question->correct_answer;

        if ($question->question_type === QuizQuestion::TYPE_MULTIPLE_CHOICE) {
            $submitted = $this->normalizeListAnswer($submittedAnswer);
            $correct = $this->normalizeListAnswer($correctAnswer);

            sort($submitted);
            sort($correct);

            return $submitted === $correct;
        }

        return (string) $this->normalizeSingleAnswer($submittedAnswer) === (string) $this->normalizeSingleAnswer($correctAnswer);
    }

    private function answerForQuestion(array $answers, QuizQuestion $question): mixed
    {
        return Arr::get($answers, (string) $question->id)
            ?? Arr::get($answers, $question->id)
            ?? Arr::get($answers, (string) $question->sort_order)
            ?? Arr::get($answers, $question->sort_order);
    }

    private function normalizeSingleAnswer(mixed $answer): mixed
    {
        if (is_array($answer)) {
            return reset($answer);
        }

        return $answer;
    }

    private function normalizeListAnswer(mixed $answer): array
    {
        if ($answer === null) {
            return [];
        }

        return array_map('strval', is_array($answer) ? array_values($answer) : [$answer]);
    }
}