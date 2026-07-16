<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyTheme;
use Illuminate\Auth\Access\Response;

class WeeklyThemePolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['professional', 'unverified_member', 'partner'], true);
    }

    public function view(User $user, WeeklyTheme $weeklyTheme): bool
    {
        return $weeklyTheme->status === 'active';
    }

    public function manage(User $user): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Only admins may manage weekly themes.');
    }

    public function create(User $user): Response
    {
        return $this->manage($user);
    }

    public function update(User $user, WeeklyTheme $weeklyTheme): Response
    {
        return $this->manage($user);
    }
}
