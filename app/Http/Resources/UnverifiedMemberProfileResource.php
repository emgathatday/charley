<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnverifiedMemberProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'photo_media_id' => $this->photo_media_id,
            'bio' => $this->bio,
            'current_institution' => $this->current_institution,
            'field_of_study' => $this->field_of_study,
            'experience_years' => $this->experience_years,
            'education' => $this->education,
            'references' => $this->references,
            'expertise_tags' => $this->expertise_tags,
            'searchable_keywords' => $this->searchable_keywords,
            'is_discoverable' => $this->is_discoverable,
            'privacy_settings' => $this->privacy_settings,
            'notification_preferences' => $this->notification_preferences,
            'linkedin_url' => $this->linkedin_url,
            'job_availability' => $this->job_availability,
            'verification_intent' => $this->verification_intent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
