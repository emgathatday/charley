<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EngineerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'photo_media_id' => $this->photo_media_id,
            'bio' => $this->bio,
            'current_company' => $this->current_company,
            'position' => $this->position,
            'plant_name' => $this->plant_name,
            'experience_years' => $this->experience_years,
            'education' => $this->education,
            'expertise_tags' => $this->expertise_tags,
            'industry_specialization' => $this->industry_specialization,
            'searchable_keywords' => $this->searchable_keywords,
            'references' => $this->references,
            'phone' => $this->phone,
            'linkedin_url' => $this->linkedin_url,
            'job_availability' => $this->job_availability,
            'reputation_points' => $this->reputation_points,
            'reputation_breakdown' => $this->reputation_breakdown,
            'ai_usage_count' => $this->ai_usage_count,
            'is_discoverable' => $this->is_discoverable,
            'privacy_settings' => $this->privacy_settings,
            'notification_preferences' => $this->notification_preferences,
            'verification_document_media_id' => $this->verification_document_media_id,
            'verification_renewed_at' => $this->verification_renewed_at,
            'renewal_reminder_sent_at' => $this->renewal_reminder_sent_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
