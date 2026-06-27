<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uploader_id' => $this->uploader_id,
            'disk' => $this->disk,
            'path' => $this->path,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'upload_context' => $this->upload_context,
            'file_category' => $this->file_category,
            'sort_order' => $this->sort_order,
            'is_watermarked' => $this->is_watermarked,
            'watermarked_file_path' => $this->watermarked_file_path,
            'streaming_url' => $this->streaming_url,
            'processing_status' => $this->processing_status,
            'processing_error' => $this->processing_error,
            'is_orphan' => $this->is_orphan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
