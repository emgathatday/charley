<?php

namespace App\Policies;

use App\Models\ContentApprovalQueue;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContentApprovalQueuePolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(?User $user): Response
    {
        return $this->deny();
    }

    public function view(?User $user, ContentApprovalQueue $contentApprovalQueue): Response
    {
        return $this->deny();
    }

    public function update(?User $user, ContentApprovalQueue $contentApprovalQueue): Response
    {
        return $this->deny();
    }

    public function approve(?User $user, ContentApprovalQueue $contentApprovalQueue): Response
    {
        return $this->deny();
    }

    private function deny(): Response
    {
        return Response::deny('Only admins can manage content approvals.');
    }
}
