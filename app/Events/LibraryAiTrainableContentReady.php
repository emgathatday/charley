<?php

namespace App\Events;

use App\Models\LibraryItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LibraryAiTrainableContentReady
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly LibraryItem $libraryItem) {}
}
