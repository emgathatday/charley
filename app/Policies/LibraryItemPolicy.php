<?php

namespace App\Policies;

use App\Models\LibraryItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LibraryItemPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, LibraryItem $libraryItem): Response
    {
        return $libraryItem->status === 'published'
            ? Response::allow()
            : Response::deny('Library item is not published.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Only admins can create library items.');
    }

    public function update(User $user, LibraryItem $libraryItem): Response
    {
        return Response::deny('Only admins can update library items.');
    }

    public function delete(User $user, LibraryItem $libraryItem): Response
    {
        return Response::deny('Only admins can delete library items.');
    }
}
