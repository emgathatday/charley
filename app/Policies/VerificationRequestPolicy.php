<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Auth\Access\Response;

class VerificationRequestPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['professional', 'unverified_member', 'partner'], true);
    }

    public function view(User $user, VerificationRequest $verificationRequest): bool
    {
        return $verificationRequest->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['professional', 'unverified_member', 'partner'], true)
            && $user->status === 'active';
    }

    public function update(User $user, VerificationRequest $verificationRequest): bool|Response
    {
        if ($verificationRequest->user_id !== $user->id) {
            return Response::deny('You can only update your own verification requests.');
        }

        if ($verificationRequest->status !== 'pending') {
            return Response::deny('Only pending verification requests can be updated.');
        }

        return $user->status === 'active';
    }

    public function approve(User $user, VerificationRequest $verificationRequest): bool|Response
    {
        return Response::deny('Only admins can approve verification requests.');
    }

    public function reject(User $user, VerificationRequest $verificationRequest): bool|Response
    {
        return Response::deny('Only admins can reject verification requests.');
    }
}
