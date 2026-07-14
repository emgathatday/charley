<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'knowledge_domain_id' => $this->knowledge_domain_id,
            'question_text' => $this->question_text,
            'question_image_media_id' => $this->question_image_media_id,
            'difficulty_level' => $this->difficulty_level,
            'status' => $this->status,
            'choices' => QuizQuestionChoiceResource::collection($this->whenLoaded('choices')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
