<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageFeedPriorityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content_type' => $this->content_type,
            'priority_weight' => $this->priority_weight,
            'is_highlighted' => $this->is_highlighted,
            'highlight_color' => $this->highlight_color,
            'is_active' => $this->is_active,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
