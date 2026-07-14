<?php

namespace App\Services;

use App\Models\ExpertiseRankTier;
use App\Models\MandatoryQuizDomain;
use App\Models\QuizAttempt;
use App\Models\RankPromotionQuizLog;
use App\Models\User;
use App\Models\UserDomainExpertise;
use App\Models\UserExpertiseRank;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpertiseRankService
{
    public function __construct(
        private readonly UserDomainExpertise $expertise,
        private readonly ExpertiseRankTier $rankTiers,
        private readonly UserExpertiseRank $userRanks,
        private readonly MandatoryQuizDomain $mandatoryDomains,
        private readonly RankPromotionQuizLog $promotionLogs,
        private readonly QuizAttempt $attempts,
    ) {
    }

    public function currentRank(User|int $user): ?UserExpertiseRank
    {
        $userId = $this->userId($user);

        return $this->userRanks->newQuery()
            ->with('rankTier')
            ->where('user_id', $userId)
            ->where('is_current', true)
            ->latest('effective_at')
            ->first();
    }

    public function setManualRank(User|int $user, ExpertiseRankTier|int|string $rankTier, ?User $promoter = null, ?string $note = null): UserExpertiseRank
    {
        return DB::transaction(function () use ($user, $rankTier, $promoter, $note): UserExpertiseRank {
            $userId = $this->userId($user);
            $tier = $this->resolveRankTier($rankTier);

            $this->userRanks->newQuery()
                ->where('user_id', $userId)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            return $this->userRanks->newQuery()->create([
                'user_id' => $userId,
                'rank_tier_id' => $tier->id,
                'promotion_source' => 'admin_manual_review',
                'promoted_by' => $promoter?->id,
                'promotion_note' => $note,
                'effective_at' => now(),
                'is_current' => true,
            ])->load('rankTier');
        });
    }

    public function upsertSelfRatedExpertise(User|int $user, int $knowledgeDomainId, float $selfRatedPercentage, int $sortOrder = 0): UserDomainExpertise
    {
        return DB::transaction(function () use ($user, $knowledgeDomainId, $selfRatedPercentage, $sortOrder): UserDomainExpertise {
            $userId = $this->userId($user);
            $cap = $this->selfRatedCapForUser($userId);
            $percentage = min(max($selfRatedPercentage, 0), $cap);

            return $this->expertise->newQuery()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'knowledge_domain_id' => $knowledgeDomainId,
                ],
                [
                    'self_rated_percentage' => $percentage,
                    'is_quiz_unlocked' => false,
                    'is_top_5_displayed' => false,
                    'sort_order' => $sortOrder,
                ]
            )->refresh()->load('knowledgeDomain');
        });
    }

    public function unlockAfterQuizPass(QuizAttempt|int $attempt): UserDomainExpertise
    {
        return DB::transaction(function () use ($attempt): UserDomainExpertise {
            $record = $this->resolveAttempt($attempt);

            if (! $record->is_passed) {
                throw new InvalidArgumentException('Only passed quiz attempts can unlock domain expertise.');
            }

            return $this->expertise->newQuery()->updateOrCreate(
                [
                    'user_id' => $record->user_id,
                    'knowledge_domain_id' => $record->knowledge_domain_id,
                ],
                [
                    'self_rated_percentage' => 100,
                    'is_quiz_unlocked' => true,
                    'unlocked_at' => now(),
                    'unlocked_via_attempt_id' => $record->id,
                ]
            )->refresh()->load(['knowledgeDomain', 'unlockedViaAttempt']);
        });
    }

    public function evaluatePromotion(User|int $user, ?int $plantTypeId = null): ?UserExpertiseRank
    {
        return DB::transaction(function () use ($user, $plantTypeId): ?UserExpertiseRank {
            $userId = $this->userId($user);
            $current = $this->currentRank($userId);
            $nextTier = $this->nextRankTier($current?->rankTier);

            if (! $nextTier) {
                return null;
            }

            $passedAttempts = $this->eligiblePassedAttempts($userId);
            $passedDomainCount = $passedAttempts->pluck('knowledge_domain_id')->unique()->count();
            $mandatoryPassedCount = $this->mandatoryPassedCount($passedAttempts, $plantTypeId);

            if (
                $passedDomainCount < $nextTier->required_quiz_count
                || $mandatoryPassedCount < $nextTier->required_mandatory_quiz_count
            ) {
                return null;
            }

            $this->userRanks->newQuery()
                ->where('user_id', $userId)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $promotion = $this->userRanks->newQuery()->create([
                'user_id' => $userId,
                'rank_tier_id' => $nextTier->id,
                'promotion_source' => 'quiz_pathway',
                'promoted_by' => null,
                'promotion_note' => 'Automatic promotion after required domain quiz passes.',
                'effective_at' => now(),
                'is_current' => true,
            ]);

            $cycleNo = $this->nextPromotionCycleNo($userId);

            foreach ($passedAttempts as $attempt) {
                $isMandatory = $this->isMandatoryDomain($attempt->knowledge_domain_id, $plantTypeId);

                $this->promotionLogs->newQuery()->firstOrCreate(
                    [
                        'user_id' => $userId,
                        'quiz_attempt_id' => $attempt->id,
                    ],
                    [
                        'knowledge_domain_id' => $attempt->knowledge_domain_id,
                        'is_mandatory' => $isMandatory,
                        'promotion_cycle_no' => $cycleNo,
                        'resulted_promotion_id' => $promotion->id,
                        'created_at' => now(),
                    ]
                );

                $attempt->forceFill(['counted_for_rank_promotion' => true])->save();
            }

            return $promotion->refresh()->load('rankTier');
        });
    }

    public function configureMandatoryDomain(int $plantTypeId, int $knowledgeDomainId, bool $active = true): MandatoryQuizDomain
    {
        return $this->mandatoryDomains->newQuery()->updateOrCreate(
            [
                'plant_type_id' => $plantTypeId,
                'knowledge_domain_id' => $knowledgeDomainId,
            ],
            ['is_active' => $active]
        )->refresh()->load(['plantType', 'knowledgeDomain']);
    }

    public function selfRatedCapForUser(User|int $user): float
    {
        $current = $this->currentRank($user);

        return $current ? (float) $current->rankTier->default_cap_percentage : 0.0;
    }

    private function eligiblePassedAttempts(int $userId): Collection
    {
        return $this->attempts->newQuery()
            ->where('user_id', $userId)
            ->where('is_passed', true)
            ->where('counted_for_rank_promotion', false)
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at')
            ->get()
            ->unique('knowledge_domain_id')
            ->values();
    }

    private function mandatoryPassedCount(Collection $attempts, ?int $plantTypeId): int
    {
        return $attempts
            ->filter(fn (QuizAttempt $attempt): bool => $this->isMandatoryDomain($attempt->knowledge_domain_id, $plantTypeId))
            ->pluck('knowledge_domain_id')
            ->unique()
            ->count();
    }

    private function isMandatoryDomain(int $knowledgeDomainId, ?int $plantTypeId): bool
    {
        return $this->mandatoryDomains->newQuery()
            ->where('knowledge_domain_id', $knowledgeDomainId)
            ->when($plantTypeId, fn ($query) => $query->where('plant_type_id', $plantTypeId))
            ->where('is_active', true)
            ->exists();
    }

    private function nextRankTier(?ExpertiseRankTier $currentTier): ?ExpertiseRankTier
    {
        $query = $this->rankTiers->newQuery()
            ->active()
            ->orderBy('rank_order');

        if ($currentTier) {
            $query->where('rank_order', '>', $currentTier->rank_order);
        }

        return $query->first();
    }

    private function resolveRankTier(ExpertiseRankTier|int|string $rankTier): ExpertiseRankTier
    {
        if ($rankTier instanceof ExpertiseRankTier) {
            return $rankTier;
        }

        $query = $this->rankTiers->newQuery();
        $record = is_numeric($rankTier)
            ? $query->find((int) $rankTier)
            : $query->where('slug', $rankTier)->first();

        if (! $record) {
            throw (new ModelNotFoundException())->setModel(ExpertiseRankTier::class, [$rankTier]);
        }

        return $record;
    }

    private function resolveAttempt(QuizAttempt|int $attempt): QuizAttempt
    {
        if ($attempt instanceof QuizAttempt) {
            return $attempt;
        }

        $record = $this->attempts->newQuery()->find($attempt);

        if (! $record) {
            throw (new ModelNotFoundException())->setModel(QuizAttempt::class, [$attempt]);
        }

        return $record;
    }

    private function nextPromotionCycleNo(int $userId): int
    {
        return ((int) $this->promotionLogs->newQuery()
            ->where('user_id', $userId)
            ->max('promotion_cycle_no')) + 1;
    }

    private function userId(User|int $user): int
    {
        return $user instanceof User ? $user->id : $user;
    }
}
