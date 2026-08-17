<?php

namespace App\Http\Resources\Qa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->when(! $this->is_anonymous, $this->user_id),
            'posted_by_admin_id' => $this->posted_by_admin_id,
            'on_behalf_of_partner_id' => $this->on_behalf_of_partner_id,
            'weekly_theme_id' => $this->weekly_theme_id,
            'plant_type_id' => $this->plant_type_id,
            'title' => $this->title,
            'body' => $this->body,
            'is_anonymous' => $this->is_anonymous,
            'status' => $this->status,
            'attachment_media_ids' => $this->attachment_media_ids,
            'weekly_theme' => new WeeklyThemeResource($this->whenLoaded('weeklyTheme')),
            'plant_type' => $this->whenLoaded('plantType'),
            'knowledge_domains' => $this->whenLoaded('knowledgeDomains'),
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
