<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerPresentationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'partner_id' => $this->partner_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'plant_type_id' => $this->plant_type_id,
            'equipment_category' => $this->equipment_category,
            'page_count' => $this->page_count,
            'download_allowed' => $this->download_allowed,
            'view_count' => $this->view_count,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'rejection_reason' => $this->rejection_reason,
            'is_ai_trainable' => $this->is_ai_trainable,
            'file_media_id' => $this->file_media_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
