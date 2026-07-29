<?php

namespace App\Services\Admin;

use App\Models\AccountPenalty;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountPenaltyService
{
    private const BLOCKING_ACTIONS = ['temporary_suspension', 'account_freeze', 'ban', 'self_freeze'];
    private const REVERSE_ACTIONS = ['unfreeze', 'self_unfreeze'];
    private const ACTION_STRENGTH = [
        'temporary_suspension' => 10,
        'self_freeze' => 20,
        'account_freeze' => 20,
        'ban' => 30,
    ];

    public function __construct(private readonly AccountPenalty $penalties) {}

    public function issue(User $user, ?User $admin, array $data): AccountPenalty
    {
        $actionType = $data['action_type'] ?? null;

        if (! in_array($actionType, $this->actionTypes(), true)) {
            throw new InvalidArgumentException('Invalid account penalty action type.');
        }

        return DB::transaction(function () use ($user, $admin, $data, $actionType): AccountPenalty {
            $this->expirePastTemporarySuspensions($user);

            $penalty = $this->penalties->newQuery()->create([
                'user_id' => $user->id,
                'admin_id' => $admin?->id,
                'action_type' => $actionType,
                'status' => $this->initialStatus($actionType),
                'reason' => $data['reason'] ?? '',
                'evidence_ref' => $data['evidence_ref'] ?? null,
                'duration_days' => $data['duration_days'] ?? null,
                'starts_at' => $data['starts_at'] ?? now(),
                'ends_at' => $data['ends_at'] ?? null,
            ]);

            if ($this->isReverseAction($actionType)) {
                $this->resolveMatchingPenalty($user, $penalty, $admin, $data['reason'] ?? null);
            }

            if ($this->isBlockingAction($actionType)) {
                $this->supersedeWeakerActivePenalties($user, $penalty, $admin);
            }

            return $penalty->refresh();
        });
    }

    public function end(AccountPenalty $penalty, ?User $admin = null, ?string $reason = null): AccountPenalty
    {
        if ($penalty->status !== AccountPenalty::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Account penalty is not active.');
        }

        $penalty->forceFill([
            'status' => AccountPenalty::STATUS_RESOLVED,
            'ends_at' => $penalty->ends_at?->isPast() ? $penalty->ends_at : now(),
            'resolved_at' => now(),
            'resolved_by' => $admin?->id,
            'resolved_reason' => $reason ?? 'Penalty ended manually.',
        ])->save();

        return $penalty->refresh();
    }

    public function activeFor(User $user): Collection
    {
        $this->expirePastTemporarySuspensions($user);

        return $this->penalties->newQuery()
            ->where('user_id', $user->id)
            ->active()
            ->latest('starts_at')
            ->get();
    }

    public function expirePastTemporarySuspensions(?User $user = null): int
    {
        $query = $this->penalties->newQuery()
            ->where('status', AccountPenalty::STATUS_ACTIVE)
            ->where('action_type', 'temporary_suspension')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now());

        if ($user !== null) {
            $query->where('user_id', $user->id);
        }

        return $query->update([
            'status' => AccountPenalty::STATUS_EXPIRED,
            'resolved_at' => now(),
            'resolved_reason' => 'Temporary suspension expired automatically.',
            'updated_at' => now(),
        ]);
    }

    private function resolveMatchingPenalty(User $user, AccountPenalty $reversePenalty, ?User $admin, ?string $reason): void
    {
        $targetAction = match ($reversePenalty->action_type) {
            'unfreeze' => 'account_freeze',
            'self_unfreeze' => 'self_freeze',
            default => null,
        };

        if ($targetAction === null) {
            return;
        }

        $targetPenalty = $this->penalties->newQuery()
            ->where('user_id', $user->id)
            ->where('status', AccountPenalty::STATUS_ACTIVE)
            ->where('action_type', $targetAction)
            ->latest('starts_at')
            ->first();

        $targetPenalty?->forceFill([
            'status' => AccountPenalty::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $admin?->id,
            'resolved_reason' => $reason ?? "Resolved by {$reversePenalty->action_type} action.",
            'resolved_by_penalty_id' => $reversePenalty->id,
        ])->save();
    }

    private function supersedeWeakerActivePenalties(User $user, AccountPenalty $newPenalty, ?User $admin): void
    {
        $newStrength = self::ACTION_STRENGTH[$newPenalty->action_type] ?? 0;

        if ($newStrength === 0) {
            return;
        }

        $weakerActions = collect(self::ACTION_STRENGTH)
            ->filter(fn (int $strength): bool => $strength < $newStrength)
            ->keys()
            ->all();

        if ($weakerActions === []) {
            return;
        }

        $this->penalties->newQuery()
            ->where('user_id', $user->id)
            ->where('status', AccountPenalty::STATUS_ACTIVE)
            ->whereKeyNot($newPenalty->id)
            ->whereIn('action_type', $weakerActions)
            ->update([
                'status' => AccountPenalty::STATUS_SUPERSEDED,
                'resolved_at' => now(),
                'resolved_by' => $admin?->id,
                'resolved_reason' => "Superseded by {$newPenalty->action_type} penalty.",
                'resolved_by_penalty_id' => $newPenalty->id,
                'updated_at' => now(),
            ]);
    }

    private function initialStatus(string $actionType): string
    {
        return $this->isBlockingAction($actionType)
            ? AccountPenalty::STATUS_ACTIVE
            : AccountPenalty::STATUS_RESOLVED;
    }

    private function isBlockingAction(string $actionType): bool
    {
        return in_array($actionType, self::BLOCKING_ACTIONS, true);
    }

    private function isReverseAction(string $actionType): bool
    {
        return in_array($actionType, self::REVERSE_ACTIONS, true);
    }

    private function actionTypes(): array
    {
        return ['warning', 'temporary_suspension', 'account_freeze', 'unfreeze', 'ban', 'self_freeze', 'self_unfreeze'];
    }
}