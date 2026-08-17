<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeDomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'plant_type_id' => $this->plant_type_id,
            'plant_type_ids' => $this->whenLoaded('plantTypes', fn () => $this->plantTypes->pluck('id')->values()),
            'plant_types' => PlantTypeResource::collection($this->whenLoaded('plantTypes')),
            'icon' => $this->icon,
            'total_question_count' => $this->total_question_count,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'quiz_questions' => QuizQuestionResource::collection($this->whenLoaded('quizQuestions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
