<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'plan_id' => $this->plan_id,
            'status' => $this->status,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'payment_method' => $this->payment_method,
            'plan' => new MemberSubscriptionPlanResource($this->whenLoaded('plan')),
        ];
    }
}
