<?php

namespace App\Http\Resources\Qa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyThemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'week_start_date' => $this->week_start_date,
            'week_end_date' => $this->week_end_date,
            'status' => $this->status,
        ];
    }
}
