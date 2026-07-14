<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertiseRankTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'min_years_experience' => $this->min_years_experience,
            'default_cap_percentage' => $this->default_cap_percentage,
            'rank_order' => $this->rank_order,
            'required_quiz_count' => $this->required_quiz_count,
            'required_mandatory_quiz_count' => $this->required_mandatory_quiz_count,
            'status' => $this->status,
            'is_active' => $this->is_active,
        ];
    }
}
