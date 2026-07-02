<?php

namespace App\Services\Admin;

use App\Models\AccountPenalty;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountPenaltyService
{
    public function __construct(private readonly AccountPenalty $penalties) {}

    public function issue(User $user, User $admin, array $data): AccountPenalty
    {
        $actionType = $data['action_type'] ?? null;

        if (! in_array($actionType, $this->actionTypes(), true)) {
            throw new InvalidArgumentException('Invalid account penalty action type.');
        }

        return DB::transaction(function () use ($user, $admin, $data, $actionType): AccountPenalty {
            return $this->penalties->newQuery()->create([
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'action_type' => $actionType,
                'reason' => $data['reason'] ?? '',
                'evidence_ref' => $data['evidence_ref'] ?? null,
                'duration_days' => $data['duration_days'] ?? null,
                'starts_at' => $data['starts_at'] ?? now(),
                'ends_at' => $data['ends_at'] ?? null,
            ]);
        });
    }

    public function end(AccountPenalty $penalty): AccountPenalty
    {
        if ($penalty->ends_at !== null && $penalty->ends_at->isPast()) {
            throw new InvalidArgumentException('Account penalty has already ended.');
        }

        $penalty->forceFill(['ends_at' => now()])->save();

        return $penalty->refresh();
    }

    public function activeFor(User $user)
    {
        return $this->penalties->newQuery()
            ->where('user_id', $user->id)
            ->active()
            ->latest('starts_at')
            ->get();
    }

    private function actionTypes(): array
    {
        return ['warning', 'temporary_suspension', 'account_freeze', 'unfreeze', 'ban', 'self_freeze', 'self_unfreeze'];
    }
}
