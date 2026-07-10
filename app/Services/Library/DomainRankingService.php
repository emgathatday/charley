<?php

namespace App\Services\Library;

use App\Models\DomainRankTier;
use App\Models\KnowledgeDomain;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserDomainPoint;
use App\Models\UserQuizBestScore;
use Illuminate\Support\Facades\DB;

class DomainRankingService
{
    public function updateBestScoreFromAttempt(QuizAttempt $quizAttempt): UserQuizBestScore
    {
        return DB::transaction(function () use ($quizAttempt): UserQuizBestScore {
            $quizAttempt->loadMissing('quiz');

            $bestScore = UserQuizBestScore::query()
                ->where('user_id', $quizAttempt->user_id)
                ->where('quiz_id', $quizAttempt->quiz_id)
                ->lockForUpdate()
                ->first();

            if (! $bestScore || $quizAttempt->score > $bestScore->best_score) {
                $bestScore = UserQuizBestScore::query()->updateOrCreate(
                    [
                        'user_id' => $quizAttempt->user_id,
                        'quiz_id' => $quizAttempt->quiz_id,
                    ],
                    [
                        'best_score' => $quizAttempt->score,
                        'best_quiz_attempt_id' => $quizAttempt->id,
                        'achieved_at' => $quizAttempt->completed_at ?? now(),
                    ],
                );
            }

            $this->recalculateUserDomainPoints($quizAttempt->user_id, $quizAttempt->quiz->knowledge_domain_id);

            return $bestScore->refresh();
        });
    }

    public function recalculateUserDomainPoints(int $userId, KnowledgeDomain|int $knowledgeDomain): UserDomainPoint
    {
        $knowledgeDomainId = $knowledgeDomain instanceof KnowledgeDomain ? $knowledgeDomain->id : $knowledgeDomain;

        return DB::transaction(function () use ($userId, $knowledgeDomainId): UserDomainPoint {
            $totalPoints = (int) UserQuizBestScore::query()
                ->join('quizzes', 'quizzes.id', '=', 'user_quiz_best_scores.quiz_id')
                ->where('user_quiz_best_scores.user_id', $userId)
                ->where('quizzes.knowledge_domain_id', $knowledgeDomainId)
                ->sum('user_quiz_best_scores.best_score');

            $rankTier = $this->rankTierForPoints($knowledgeDomainId, $totalPoints);

            return UserDomainPoint::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'knowledge_domain_id' => $knowledgeDomainId,
                ],
                [
                    'total_points' => $totalPoints,
                    'current_rank_tier_id' => $rankTier?->id,
                    'last_recalculated_at' => now(),
                ],
            )->refresh();
        });
    }

    public function recalculateForQuiz(Quiz $quiz, int $userId): UserDomainPoint
    {
        return $this->recalculateUserDomainPoints($userId, $quiz->knowledge_domain_id);
    }

    public function rankTierForPoints(KnowledgeDomain|int $knowledgeDomain, int $totalPoints): ?DomainRankTier
    {
        $knowledgeDomainId = $knowledgeDomain instanceof KnowledgeDomain ? $knowledgeDomain->id : $knowledgeDomain;

        return DomainRankTier::query()
            ->where('knowledge_domain_id', $knowledgeDomainId)
            ->where('min_points', '<=', $totalPoints)
            ->orderByDesc('sort_order')
            ->first();
    }

    public function currentRank(int $userId, KnowledgeDomain|int $knowledgeDomain): ?UserDomainPoint
    {
        $knowledgeDomainId = $knowledgeDomain instanceof KnowledgeDomain ? $knowledgeDomain->id : $knowledgeDomain;

        return UserDomainPoint::query()
            ->with(['knowledgeDomain', 'currentRankTier'])
            ->where('user_id', $userId)
            ->where('knowledge_domain_id', $knowledgeDomainId)
            ->first();
    }
}