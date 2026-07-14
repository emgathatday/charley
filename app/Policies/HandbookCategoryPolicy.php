<?php

namespace App\Policies;

use App\Models\HandbookCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HandbookCategoryPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, HandbookCategory $handbookCategory): Response
    {
        return $handbookCategory->status === 'published'
            ? Response::allow()
            : Response::deny('Handbook category is not published.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Only admins can create handbook categories.');
    }

    public function update(User $user, HandbookCategory $handbookCategory): Response
    {
        return Response::deny('Only admins can update handbook categories.');
    }

    public function delete(User $user, HandbookCategory $handbookCategory): Response
    {
        return Response::deny('Only admins can delete handbook categories.');
    }

    public function manage(User $user): Response
    {
        return Response::deny('Only admins can manage handbook categories.');
    }

    public function updateHotspots(User $user, HandbookCategory $handbookCategory): Response
    {
        return Response::deny('Only admins can update handbook layout hotspots.');
    }
}
