<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content_blocks' => $this->content_blocks,
            'status' => $this->status,
            'is_system_page' => $this->is_system_page,
            'view_count' => $this->view_count,
            'seo_meta' => $this->seo_meta,
            'user_id' => $this->user_id,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
