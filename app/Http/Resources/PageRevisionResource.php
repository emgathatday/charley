<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'content_blocks' => $this->content_blocks,
            'changed_by' => $this->changed_by,
            'change_summary' => $this->change_summary,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
