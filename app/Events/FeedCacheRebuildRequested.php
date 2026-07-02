<?php

namespace App\Events;

class FeedCacheRebuildRequested
{
    public function __construct(
        public readonly ?int $userId = null,
        public readonly string $reason = 'manual',
    ) {
    }
}
