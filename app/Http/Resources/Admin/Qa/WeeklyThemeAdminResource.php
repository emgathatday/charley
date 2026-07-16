<?php

namespace App\Http\Resources\Admin\Qa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyThemeAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'week_start_date' => $this->week_start_date,
            'week_end_date' => $this->week_end_date,
            'created_by_admin_id' => $this->created_by_admin_id,
            'status' => $this->status,
            'created_by_admin' => $this->whenLoaded('createdByAdmin'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
