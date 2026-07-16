<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AnswerPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['professional', 'unverified_member', 'partner'], true);
    }

    public function view(User $user, Answer $answer): bool
    {
        return $answer->question?->status === 'published' || $answer->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['professional', 'unverified_member', 'partner'], true);
    }

    public function moderate(User $user): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Only admins may moderate answers.');
    }

    public function feature(User $user, Answer $answer): Response
    {
        return $this->moderate($user);
    }

    public function unfeature(User $user, Answer $answer): Response
    {
        return $this->moderate($user);
    }

    public function reorder(User $user): Response
    {
        return $this->moderate($user);
    }
}
