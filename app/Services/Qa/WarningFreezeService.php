<?php

namespace App\Services\Qa;

use App\Models\QaModerationWarning;
use App\Models\QaUserWarningSummary;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WarningFreezeService
{
    public function assertUserCanSubmit(int $userId): void
    {
        if ($this->isUserFrozen($userId)) {
            throw new InvalidArgumentException('User is frozen from Q&A submissions after confirmed moderation warnings.');
        }
    }

    public function isUserFrozen(int $userId): bool
    {
        return QaUserWarningSummary::query()
            ->where('user_id', $userId)
            ->where('is_frozen', true)
            ->exists();
    }

    public function confirmWarning(QaModerationWarning $warning, ?int $reviewedBy = null): QaUserWarningSummary
    {
        return DB::transaction(function () use ($warning, $reviewedBy): QaUserWarningSummary {
            $warning->forceFill([
                'status' => 'confirmed',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ])->save();

            return $this->rebuildSummary($warning->user_id);
        });
    }

    public function markReviewed(QaModerationWarning $warning, string $status, ?int $reviewedBy = null): QaUserWarningSummary
    {
        if (! in_array($status, ['safe', 'confirmed', 'dismissed'], true)) {
            throw new InvalidArgumentException('Warning review status must be safe, confirmed, or dismissed.');
        }

        return $status === 'confirmed'
            ? $this->confirmWarning($warning, $reviewedBy)
            : DB::transaction(function () use ($warning, $status, $reviewedBy): QaUserWarningSummary {
                $warning->forceFill([
                    'status' => $status,
                    'reviewed_by' => $reviewedBy,
                    'reviewed_at' => now(),
                ])->save();

                return $this->rebuildSummary($warning->user_id);
            });
    }

    public function rebuildSummary(int $userId): QaUserWarningSummary
    {
        $confirmedWarnings = QaModerationWarning::query()
            ->where('user_id', $userId)
            ->where('status', 'confirmed');
        $confirmedCount = (clone $confirmedWarnings)->count();
        $lastWarningAt = (clone $confirmedWarnings)->latest('created_at')->value('created_at');
        $isFrozen = $confirmedCount >= 3;

        return QaUserWarningSummary::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'confirmed_warning_count' => $confirmedCount,
                'last_warning_at' => $lastWarningAt,
                'is_frozen' => $isFrozen,
                'frozen_at' => $isFrozen ? now() : null,
                'frozen_reason' => $isFrozen ? 'Reached 3 confirmed Q&A moderation warnings.' : null,
                'updated_at' => now(),
            ],
        );
    }
}
