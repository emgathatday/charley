<?php

namespace App\Http\Resources\Admin\Qa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerModerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'user_id' => $this->user_id,
            'is_anonymous' => $this->is_anonymous,
            'body' => $this->body,
            'is_admin_featured' => $this->is_admin_featured,
            'confidence_level' => $this->confidence_level,
            'admin_rank_order' => $this->admin_rank_order,
            'attachment_media_ids' => $this->attachment_media_ids,
            'question' => $this->whenLoaded('question'),
            'user' => $this->whenLoaded('user'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
