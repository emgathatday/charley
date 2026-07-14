<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class LibraryAdminPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function manage(User $user): Response
    {
        return Response::deny('Only admins can manage library administration resources.');
    }
}
