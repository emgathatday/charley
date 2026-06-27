<?php

namespace App\Events;

use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VerificationRejectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly VerificationRequest $verificationRequest,
        public readonly User $reviewer,
    ) {
    }
}
