<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFeedCacheResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'feedable_type' => $this->feedable_type,
            'feedable_id' => $this->feedable_id,
            'priority_score' => $this->priority_score,
            'source_reason' => $this->source_reason,
            'is_seen' => $this->is_seen,
            'created_at' => $this->created_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'feedable' => $this->whenLoaded('feedable'),
        ];
    }
}
