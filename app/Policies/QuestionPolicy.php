<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuestionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['professional', 'unverified_member', 'partner'], true);
    }

    public function view(User $user, Question $question): bool
    {
        return $question->status === 'published' || $question->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['professional', 'unverified_member', 'partner'], true);
    }

    public function postOnBehalf(User $user): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Only admins may post questions on behalf of partners.');
    }

    public function moderate(User $user): Response
    {
        return $user->role === 'admin'
            ? Response::allow()
            : Response::deny('Only admins may moderate questions.');
    }

    public function publish(User $user, Question $question): Response
    {
        return $this->moderate($user);
    }

    public function hide(User $user, Question $question): Response
    {
        return $this->moderate($user);
    }

    public function flag(User $user, Question $question): Response
    {
        return $this->moderate($user);
    }
}
