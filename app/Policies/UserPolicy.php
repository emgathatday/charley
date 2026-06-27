<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id && $user->status === 'active';
    }

    public function freeze(User $user, User $target): bool|Response
    {
        if ($user->id !== $target->id) {
            return Response::deny('You can only freeze your own account.');
        }

        return $target->status === 'active';
    }

    public function manageSecurity(User $user, User $target): bool
    {
        return $user->id === $target->id && $user->status === 'active';
    }

    public function manageSocialAccounts(User $user, User $target): bool
    {
        return $user->id === $target->id
            && in_array($user->role, ['professional', 'unverified_member', 'partner'], true)
            && $user->status === 'active';
    }
}
