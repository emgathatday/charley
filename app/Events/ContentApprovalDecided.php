<?php

namespace App\Events;

use App\Models\ContentApprovalQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentApprovalDecided
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ContentApprovalQueue $approval) {}
}
