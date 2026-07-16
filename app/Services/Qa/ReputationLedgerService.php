<?php

namespace App\Services\Qa;

use App\Models\PointTransaction;
use App\Models\ReputationRankTier;
use App\Models\UserReputation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReputationLedgerService
{
    public function recordQuestionPoints(int $userId, int $questionId, int $points): PointTransaction
    {
        return $this->record($userId, $points, 'question', $questionId);
    }

    public function recordAnswerPoints(int $userId, int $answerId, int $points): PointTransaction
    {
        return $this->record($userId, $points, 'answer', $answerId);
    }

    public function recordManualAdjustment(int $userId, int $points, string $reason, int $performedBy): PointTransaction
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('reason is required for manual reputation adjustments.');
        }

        return $this->record($userId, $points, 'manual_adjustment', null, $reason, $performedBy);
    }

    public function recalculateUserReputation(int $userId): UserReputation
    {
        return DB::transaction(function () use ($userId): UserReputation {
            $totalPoints = (int) PointTransaction::query()
                ->where('user_id', $userId)
                ->sum('points');

            $rankTier = ReputationRankTier::query()
                ->where('min_points', '<=', $totalPoints)
                ->orderByDesc('min_points')
                ->first();

            return UserReputation::query()->updateOrCreate(
                ['user_id' => $userId],
                [
                    'total_points' => $totalPoints,
                    'current_star_rank' => $rankTier?->star_level ?? 1,
                    'updated_at' => now(),
                ],
            );
        });
    }

    private function record(
        int $userId,
        int $points,
        string $sourceType,
        ?int $sourceId = null,
        ?string $reason = null,
        ?int $performedBy = null,
    ): PointTransaction {
        return DB::transaction(function () use ($userId, $points, $sourceType, $sourceId, $reason, $performedBy): PointTransaction {
            $transaction = PointTransaction::query()->create([
                'user_id' => $userId,
                'points' => $points,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reason' => $reason,
                'performed_by' => $performedBy,
                'created_at' => now(),
            ]);

            $this->recalculateUserReputation($userId);

            return $transaction;
        });
    }
}
