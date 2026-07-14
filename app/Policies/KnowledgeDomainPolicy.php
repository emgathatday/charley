<?php

namespace App\Policies;

use App\Models\KnowledgeDomain;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class KnowledgeDomainPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, KnowledgeDomain $knowledgeDomain): Response
    {
        return $knowledgeDomain->is_active ? Response::allow() : Response::deny('Knowledge domain is inactive.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Only admins can create knowledge domains.');
    }

    public function update(User $user, KnowledgeDomain $knowledgeDomain): Response
    {
        return Response::deny('Only admins can update knowledge domains.');
    }

    public function delete(User $user, KnowledgeDomain $knowledgeDomain): Response
    {
        return Response::deny('Only admins can delete knowledge domains.');
    }
}
