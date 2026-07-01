<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tier_id' => $this->tier_id,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'tier' => new SubscriptionTierResource($this->whenLoaded('tier')),
            'payments' => SubscriptionPaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
