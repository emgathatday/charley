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
            'expertise_level_id' => $this->expertise_level_id,
            'plant_type_id' => $this->plant_type_id,
            'handbook_category_id' => $this->handbook_category_id,
            'source' => $this->source,
            'assigned_by' => $this->assigned_by,
            'quiz_attempt_id' => $this->quiz_attempt_id,
            'notes' => $this->notes,
            'is_current' => $this->is_current,
            'assigned_at' => $this->assigned_at?->toISOString(),
            'expertise_level' => $this->whenLoaded('expertiseLevel', fn () => [
                'id' => $this->expertiseLevel?->id,
                'name' => $this->expertiseLevel?->name,
                'code' => $this->expertiseLevel?->code,
                'badge_icon' => $this->expertiseLevel?->badge_icon,
                'sort_order' => $this->expertiseLevel?->sort_order,
            ]),
            'plant_type' => $this->whenLoaded('plantType', fn () => [
                'id' => $this->plantType?->id,
                'name' => $this->plantType?->name,
                'slug' => $this->plantType?->slug,
            ]),
            'handbook_category' => $this->whenLoaded('handbookCategory', fn () => [
                'id' => $this->handbookCategory?->id,
                'title' => $this->handbookCategory?->title,
                'slug' => $this->handbookCategory?->slug,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
