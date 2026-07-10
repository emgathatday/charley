<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserExpertiseRank;
use Illuminate\Auth\Access\Response;

class UserExpertiseRankPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function view(User $user, UserExpertiseRank $rank): Response
    {
        return $user->id === $rank->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to view this expertise rank.');
    }

    public function viewCurrent(User $user): Response
    {
        return Response::allow();
    }

    public function assign(User $user): Response
    {
        return Response::deny('Only admins can assign expertise ranks.');
    }
}
