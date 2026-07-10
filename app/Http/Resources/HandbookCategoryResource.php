<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HandbookCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'plant_type_id' => $this->plant_type_id,
            'parent_id' => $this->parent_id,
            'layout_image_media_id' => $this->layout_image_media_id,
            'map_coordinates' => $this->map_coordinates,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'plant_type' => new PlantTypeResource($this->whenLoaded('plantType')),
            'layout_image' => new MediaFileResource($this->whenLoaded('layoutImage')),
            'children' => self::collection($this->whenLoaded('children')),
            'articles_count' => $this->whenCounted('articles'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
