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
            'quiz_id' => $this->quiz_id,
            'user_id' => $this->user_id,
            'attempt_number' => $this->attempt_number,
            'answers_submitted' => $this->answers_submitted,
            'score' => $this->score,
            'max_possible_score' => $this->max_possible_score,
            'score_percent' => $this->scorePercent(),
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'quiz' => new QuizResource($this->whenLoaded('quiz')),
            'best_score' => $this->whenLoaded('bestScore', fn () => $this->bestScore ? [
                'id' => $this->bestScore->id,
                'best_score' => $this->bestScore->best_score,
                'best_quiz_attempt_id' => $this->bestScore->best_quiz_attempt_id,
                'achieved_at' => $this->bestScore->achieved_at,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}