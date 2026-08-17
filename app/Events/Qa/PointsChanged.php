<?php

namespace App\Events\Qa;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PointsChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $points,
        public readonly string $sourceType,
        public readonly ?int $sourceId = null,
        public readonly ?int $pointTransactionId = null,
    ) {}
}
