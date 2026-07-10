<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryItemHotspotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'library_item_id' => $this->library_item_id,
            'knowledge_domain_id' => $this->knowledge_domain_id,
            'label' => $this->label,
            'display_label' => $this->displayLabel(),
            'shape_type' => $this->shape_type,
            'coordinates' => $this->coordinates,
            'sort_order' => $this->sort_order,
            'knowledge_domain' => new KnowledgeDomainResource($this->whenLoaded('knowledgeDomain')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}