<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDomainPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'knowledge_domain_id' => $this->knowledge_domain_id,
            'total_points' => $this->total_points,
            'current_rank_tier_id' => $this->current_rank_tier_id,
            'last_recalculated_at' => $this->last_recalculated_at,
            'knowledge_domain' => new KnowledgeDomainResource($this->whenLoaded('knowledgeDomain')),
            'current_rank_tier' => new DomainRankTierResource($this->whenLoaded('currentRankTier')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}