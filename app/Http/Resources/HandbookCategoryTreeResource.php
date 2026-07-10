<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HandbookCategoryTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'title' => $this->resource['title'],
            'slug' => $this->resource['slug'],
            'plant_type_id' => $this->resource['plant_type_id'],
            'map_coordinates' => $this->resource['map_coordinates'],
            'sort_order' => $this->resource['sort_order'],
            'status' => $this->resource['status'],
            'children' => self::collection(collect($this->resource['children'] ?? [])),
        ];
    }
}
