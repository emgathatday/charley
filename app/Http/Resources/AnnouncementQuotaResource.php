<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementQuotaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'period' => $this->period,
            'used_count' => $this->used_count,
            'quota_limit' => $this->quota_limit,
            'remaining_count' => max(0, $this->quota_limit - $this->used_count),
        ];
    }
}
