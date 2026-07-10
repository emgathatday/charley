<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HandbookRelatedItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'handbook_article_id' => $this->handbook_article_id,
            'relatable_type' => $this->relatable_type,
            'relatable_id' => $this->relatable_id,
            'relation_type' => $this->relation_type,
            'sort_order' => $this->sort_order,
            'relatable' => $this->whenLoaded('relatable'),
        ];
    }
}
