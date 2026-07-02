<?php

namespace App\Events;

use App\Models\AdminIntegration;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminIntegrationTokenExpiring
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly AdminIntegration $integration) {}
}
