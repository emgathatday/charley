<?php

namespace App\Events;

use App\Models\LibraryAccessLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LibraryAccessLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly LibraryAccessLog $accessLog) {}
}
