<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LibraryAccessAnalyticsAggregated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $itemsUpdated,
        public readonly int $viewsCounted,
        public readonly int $downloadsCounted,
    ) {}
}