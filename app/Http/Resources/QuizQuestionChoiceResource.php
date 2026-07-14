<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionChoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'choice_text' => $this->choice_text,
            'is_correct' => $this->is_correct,
            'explanation' => $this->explanation,
            'sort_order' => $this->sort_order,
        ];
    }
}
