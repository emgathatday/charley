<?php

namespace App\Http\Resources\Admin\Qa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReputationAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'points' => $this->points,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'reason' => $this->reason,
            'performed_by' => $this->performed_by,
            'created_at' => $this->created_at,
        ];
    }
}
