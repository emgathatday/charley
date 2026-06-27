<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchIndexEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'indexable_type' => $this->indexable_type,
            'indexable_id' => $this->indexable_id,
            'searchable_text' => $this->searchable_text,
            'structured_data' => $this->structured_data,
            'search_context' => $this->search_context,
            'is_discoverable' => $this->is_discoverable,
            'last_indexed_at' => $this->last_indexed_at,
        ];
    }
}
