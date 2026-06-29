<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'monthly_price' => $this->monthly_price,
            'ai_monthly_limit' => $this->ai_monthly_limit,
            'announcement_frequency' => $this->announcement_frequency,
            'announcement_limit' => $this->announcement_limit,
            'can_host_webinar' => $this->can_host_webinar,
            'can_initiate_message' => $this->can_initiate_message,
            'can_create_poll' => $this->can_create_poll,
            'can_publish_events' => $this->can_publish_events,
            'is_active' => $this->is_active,
        ];
    }
}
