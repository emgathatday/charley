<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankPromotionQuizLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'quiz_attempt_id' => $this->quiz_attempt_id,
            'knowledge_domain_id' => $this->knowledge_domain_id,
            'is_mandatory' => $this->is_mandatory,
            'promotion_cycle_no' => $this->promotion_cycle_no,
            'resulted_promotion_id' => $this->resulted_promotion_id,
            'created_at' => $this->created_at,
        ];
    }
}
