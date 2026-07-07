<?php

namespace App\Events;

use App\Models\LibraryItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LibraryUploadProcessingRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly LibraryItem $libraryItem) {}
}
