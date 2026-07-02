<?php

namespace App\Policies;

use App\Models\AccountPenalty;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccountPenaltyPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(?User $user): Response
    {
        return $this->deny();
    }

    public function view(?User $user, AccountPenalty $accountPenalty): Response
    {
        return $this->deny();
    }

    public function create(?User $user): Response
    {
        return $this->deny();
    }

    public function update(?User $user, AccountPenalty $accountPenalty): Response
    {
        return $this->deny();
    }

    private function deny(): Response
    {
        return Response::deny('Only admins can manage account penalties.');
    }
}
