<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuizPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, Quiz $quiz): Response
    {
        return $quiz->status === Quiz::STATUS_PUBLISHED
            ? Response::allow()
            : Response::deny('Quiz is not published.');
    }

    public function attempt(User $user, Quiz $quiz): Response
    {
        if ($quiz->status !== Quiz::STATUS_PUBLISHED) {
            return Response::deny('Quiz is not published.');
        }

        return in_array($user->role, ['professional', 'partner'], true)
            ? Response::allow()
            : Response::deny('Only verified users can attempt library quizzes.');
    }

    public function manage(User $user): Response
    {
        return Response::deny('Only admins can manage quizzes.');
    }

    public function update(User $user, Quiz $quiz): Response
    {
        return $this->manage($user);
    }

    public function delete(User $user, Quiz $quiz): Response
    {
        return $this->manage($user);
    }
}
