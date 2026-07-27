<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'partner_id' => $this->partner_id,
            'name' => $this->name,
            'category' => $this->category,
            'item_type' => $this->item_type,
            'description' => $this->description,
            'image_media_id' => $this->image_media_id,
            'datasheet_media_id' => $this->datasheet_media_id,
            'keywords' => $this->keywords,
            'is_active' => $this->is_active,
            'image_media' => MediaFileResource::make($this->whenLoaded('imageMedia')),
            'datasheet_media' => MediaFileResource::make($this->whenLoaded('datasheetMedia')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
