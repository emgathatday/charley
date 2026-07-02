<?php

namespace App\Events;

class HomepageFeedPriorityEffectsRefreshRequested
{
    public function __construct(
        public readonly ?string $contentType = null,
        public readonly string $reason = 'priority_refresh',
    ) {
    }
}
