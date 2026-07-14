<?php

namespace App\Events;

use App\Models\LibraryItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LibraryItemIngestionQueued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly LibraryItem $libraryItem,
        public readonly bool $mediaExtractionQueued,
        public readonly bool $aiIngestionQueued,
    ) {}
}