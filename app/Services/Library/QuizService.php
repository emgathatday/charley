<?php

namespace App\Services\Library;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    public function __construct(
        private readonly ExpertiseRankingService $expertiseRankingService,
    ) {}

    public function startAttempt(Quiz $quiz, User $user): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $user): QuizAttempt {
            $attemptCount = QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->count();

            if ($quiz->max_attempts_per_user !== null && $attemptCount >= $quiz->max_attempts_per_user) {
                throw ValidationException::withMessages([
                    'quiz' => 'The maximum number of attempts for this quiz has been reached.',
                ]);
            }

            return QuizAttempt::query()->create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'attempt_number' => $attemptCount + 1,
                'answers_submitted' => null,
                'score' => 0,
                'max_possible_score' => $this->maxPossibleScore($quiz),
                'score_percent' => 0,
                'passed' => false,
                'started_at' => now(),
                'completed_at' => null,
            ]);
        });
    }

    public function submitAttempt(QuizAttempt $attempt, array $answersSubmitted): QuizAttempt
    {
        if ($attempt->completed_at !== null) {
            throw ValidationException::withMessages([
                'quiz_attempt' => 'This quiz attempt has already been completed.',
            ]);
        }

        return DB::transaction(function () use ($attempt, $answersSubmitted): QuizAttempt {
            $attempt->loadMissing('quiz.questions');

            $questions = $attempt->quiz->questions;
            $maxPossibleScore = $questions->sum('points');
            $score = $this->scoreAnswers($questions, $answersSubmitted);
            $scorePercent = $maxPossibleScore > 0 ? round(($score / $maxPossibleScore) * 100, 2) : 0;

            $attempt->forceFill([
                'answers_submitted' => $answersSubmitted,
                'score' => $score,
                'max_possible_score' => $maxPossibleScore,
                'score_percent' => $scorePercent,
                'passed' => $scorePercent >= $attempt->quiz->passing_score_percent,
                'completed_at' => now(),
            ])->save();

            $attempt = $attempt->refresh();
            $attempt->loadMissing('quiz.targetExpertiseLevel', 'quiz.plantType', 'quiz.handbookCategory', 'user');

            if ($attempt->passed) {
                $this->expertiseRankingService->promoteFromQuizAttempt($attempt);
            }

            return $attempt;
        });
    }

    public function createAndSubmitAttempt(Quiz $quiz, User $user, array $answersSubmitted): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $user, $answersSubmitted): QuizAttempt {
            $attempt = $this->startAttempt($quiz, $user);

            return $this->submitAttempt($attempt, $answersSubmitted);
        });
    }

    public function maxPossibleScore(Quiz $quiz): int
    {
        return (int) $quiz->questions()->sum('points');
    }

    /**
     * @param Collection<int, QuizQuestion> $questions
     */
    private function scoreAnswers(Collection $questions, array $answersSubmitted): int
    {
        return $questions->reduce(function (int $score, QuizQuestion $question) use ($answersSubmitted): int {
            $submittedAnswer = $this->submittedAnswerForQuestion($answersSubmitted, $question->id);

            if ($submittedAnswer === $this->normalizedAnswer($question->correct_answer ?? [])) {
                return $score + $question->points;
            }

            return $score;
        }, 0);
    }

    private function submittedAnswerForQuestion(array $answersSubmitted, int $questionId): array
    {
        $answer = $answersSubmitted[$questionId] ?? $answersSubmitted[(string) $questionId] ?? [];

        if (is_array($answer) && array_key_exists('answer', $answer)) {
            $answer = $answer['answer'];
        }

        return $this->normalizedAnswer($answer);
    }

    private function normalizedAnswer(mixed $answer): array
    {
        $normalized = is_array($answer) ? $answer : [$answer];

        $normalized = collect($normalized)
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->map(fn ($value): int => (int) $value)
            ->sort()
            ->values()
            ->all();

        return $normalized;
    }
}
