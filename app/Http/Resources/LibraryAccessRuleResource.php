<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryAccessRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'partner_tier' => $this->partner_tier,
            'can_view' => $this->can_view,
            'can_download' => $this->can_download,
            'can_copy_paste' => $this->can_copy_paste,
            'requires_watermark' => $this->requires_watermark,
            'max_downloads_per_month' => $this->max_downloads_per_month,
            'notes' => $this->notes,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
