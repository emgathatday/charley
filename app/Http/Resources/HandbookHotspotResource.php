<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HandbookHotspotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'title' => $this->resource['title'],
            'slug' => $this->resource['slug'],
            'plant_type_id' => $this->resource['plant_type_id'],
            'layout_image_media_id' => $this->resource['layout_image_media_id'],
            'layout_image' => $this->resource['layout_image'],
            'map_coordinates' => $this->resource['map_coordinates'],
        ];
    }
}
