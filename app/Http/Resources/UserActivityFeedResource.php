<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserActivityFeedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'activity_type' => $this->activity_type,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at,
        ];
    }
}
