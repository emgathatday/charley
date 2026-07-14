<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'knowledge_domain_id' => $this->knowledge_domain_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'time_limit_minutes' => $this->time_limit_minutes,
            'max_attempts_per_user' => $this->max_attempts_per_user,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'max_possible_score' => $this->whenLoaded('questions', fn () => $this->maxPossibleScore()),
            'knowledge_domain' => new KnowledgeDomainResource($this->whenLoaded('knowledgeDomain')),
            'questions' => $this->whenLoaded('questions', fn () => $this->questions->map(fn ($question) => [
                'id' => $question->id,
                'quiz_id' => $question->quiz_id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'options' => $question->options,
                'correct_answer' => $request->user()?->role === 'admin' ? $question->correct_answer : null,
                'points' => $question->points,
                'explanation' => $question->explanation,
                'sort_order' => $question->sort_order,
            ])->values()),
            'attempts_count' => $this->whenCounted('attempts'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}