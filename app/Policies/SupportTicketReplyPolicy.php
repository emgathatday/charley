<?php

namespace App\Policies;

use App\Models\SupportTicketReply;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SupportTicketReplyPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function view(?User $user, SupportTicketReply $supportTicketReply): Response
    {
        return $this->deny();
    }

    public function create(?User $user): Response
    {
        return $this->deny();
    }

    private function deny(): Response
    {
        return Response::deny('Only admins can manage support ticket replies.');
    }
}
