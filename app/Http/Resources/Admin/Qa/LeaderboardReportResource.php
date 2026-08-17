<?php

namespace App\Http\Resources\Admin\Qa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'year_month' => $this->year_month,
            'total_points_in_month' => $this->total_points_in_month,
            'rank_position' => $this->rank_position,
            'user' => $this->whenLoaded('user'),
            'created_at' => $this->created_at,
        ];
    }
}
