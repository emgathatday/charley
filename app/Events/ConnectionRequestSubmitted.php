<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionRequestSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $connectionRequestId, public readonly int $requesterId, public readonly int $targetUserId,
    ) {}
}
