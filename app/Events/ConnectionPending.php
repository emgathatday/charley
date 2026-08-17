<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionPending
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $connectionId, public readonly int $requesterId, public readonly int $receiverId,
    ) {}
}
