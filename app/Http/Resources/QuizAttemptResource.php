<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'knowledge_domain_id' => $this->knowledge_domain_id,
            'total_questions' => $this->total_questions,
            'correct_count' => $this->correct_count,
            'score_percentage' => $this->score_percentage,
            'pass_threshold' => $this->pass_threshold,
            'is_passed' => $this->is_passed,
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'next_attempt_allowed_at' => $this->next_attempt_allowed_at,
            'attempt_questions' => $this->whenLoaded('attemptQuestions'),
        ];
    }
}
