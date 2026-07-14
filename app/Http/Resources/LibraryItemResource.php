<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryItemResource extends JsonResource
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
            'content' => $this->content,
            'plant_type_id' => $this->plant_type_id,
            'author' => $this->author,
            'source' => $this->source,
            'published_year' => $this->published_year,
            'access_level' => $this->access_level,
            'download_allowed' => $this->download_allowed,
            'copy_paste_disabled' => $this->copy_paste_disabled,
            'download_count' => $this->download_count,
            'status' => $this->status,
            'is_ai_trainable' => $this->is_ai_trainable,
            'content_type' => $this->content_type,
            'item_type' => $this->item_type,
            'view_count' => $this->view_count,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'year' => $this->year,
            'file_media_id' => $this->file_media_id,
            'category' => LibraryCategoryResource::make($this->whenLoaded('category')),
            'file_media' => MediaFileResource::make($this->whenLoaded('fileMedia')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
