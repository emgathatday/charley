<?php

namespace App\Events;

use App\Models\AccountPenalty;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountPenaltyIssued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly AccountPenalty $penalty) {}
}
