<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountPenaltyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'action_type' => $this->action_type,
            'reason' => $this->reason,
            'evidence_ref' => $this->evidence_ref,
            'duration_days' => $this->duration_days,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'admin_id' => $this->admin_id,
        ];
    }
}
