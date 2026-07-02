<?php

namespace App\Events;

use App\Models\SupportTicketReply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketReplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly SupportTicketReply $reply) {}
}
