<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserReputation;
use Illuminate\Auth\Access\Response;

class ReputationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['professional', 'unverified_member', 'partner'], true);
    }

    public function view(User $user, UserReputation $userReputation): bool
    {
        return $userReputation->user_id === $user->id;
    }

    public function adjust(User $user): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Only admins may adjust reputation points.');
    }

    public function manageLeaderboard(User $user): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Only admins may manage leaderboard settings and snapshots.');
    }
}
