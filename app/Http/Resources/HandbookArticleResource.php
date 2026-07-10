<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HandbookArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'content' => $this->when($request->routeIs('*.show'), $this->content),
            'optimization_guidance' => $this->optimization_guidance,
            'failure_modes' => $this->failure_modes,
            'status' => $this->status,
            'is_ai_trainable' => $this->is_ai_trainable,
            'ai_shortcut_config' => $this->ai_shortcut_config,
            'view_count' => $this->view_count,
            'process_description' => $this->process_description,
            'category' => new HandbookCategoryResource($this->whenLoaded('category')),
            'metadata' => HandbookMetadataResource::collection($this->whenLoaded('metadata')),
            'related_items' => HandbookRelatedItemResource::collection($this->whenLoaded('relatedItems')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
