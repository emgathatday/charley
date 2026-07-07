<?php

namespace App\Policies;

use App\Models\LibraryAccessRule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LibraryAccessRulePolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): Response
    {
        return $this->deny();
    }

    public function create(User $user): Response
    {
        return $this->deny();
    }

    public function update(User $user, LibraryAccessRule $libraryAccessRule): Response
    {
        return $this->deny();
    }

    public function manage(User $user): Response
    {
        return $this->deny();
    }

    private function deny(): Response
    {
        return Response::deny('Only admins can manage library access rules.');
    }
}
