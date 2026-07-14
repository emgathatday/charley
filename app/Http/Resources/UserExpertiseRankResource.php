<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserExpertiseRankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'rank_tier_id' => $this->rank_tier_id,
            'promotion_source' => $this->promotion_source,
            'promoted_by' => $this->promoted_by,
            'promotion_note' => $this->promotion_note,
            'effective_at' => $this->effective_at,
            'is_current' => $this->is_current,
            'rank_tier' => ExpertiseRankTierResource::make($this->whenLoaded('rankTier')),
        ];
    }
}
