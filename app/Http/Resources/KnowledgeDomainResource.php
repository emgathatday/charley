<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeDomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'rank_tiers' => DomainRankTierResource::collection($this->whenLoaded('rankTiers')),
            'quizzes_count' => $this->whenCounted('quizzes'),
            'hotspots_count' => $this->whenCounted('hotspots'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}