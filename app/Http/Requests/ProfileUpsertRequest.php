<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfileUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'photo_media_id' => ['nullable', 'integer', Rule::exists('media_files', 'id')],
            'bio' => ['nullable', 'string'],
            'current_company' => ['nullable', 'string', 'max:255'],
            'current_institution' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'plant_name' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'education' => ['nullable', 'string'],
            'expertise_tags' => ['nullable', 'array'],
            'expertise_tags.*' => ['string', 'max:120'],
            'industry_specialization' => ['nullable', 'array'],
            'industry_specialization.*' => ['string', 'max:120'],
            'searchable_keywords' => ['nullable', 'array'],
            'searchable_keywords.*' => ['string', 'max:120'],
            'references' => ['nullable', 'array'],
            'phone' => ['nullable', 'string', 'max:50'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'job_availability' => ['nullable', Rule::in(['open', 'not_looking', 'open_to_opportunities'])],
            'reputation_points' => ['nullable', 'integer', 'min:0'],
            'reputation_breakdown' => ['nullable', 'array'],
            'ai_usage_count' => ['nullable', 'integer', 'min:0'],
            'is_discoverable' => ['nullable', 'boolean'],
            'privacy_settings' => ['nullable', 'array'],
            'notification_preferences' => ['nullable', 'array'],
            'verification_intent' => ['nullable', 'boolean'],
            'verification_document_media_id' => ['nullable', 'integer', Rule::exists('media_files', 'id')],
            'verification_renewed_at' => ['nullable', 'date'],
            'renewal_reminder_sent_at' => ['nullable', 'date'],
            'plant_type_ids' => ['nullable', 'array'],
            'plant_type_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('plant_types', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'primary_plant_type_id' => [
                'nullable',
                'integer',
                Rule::exists('plant_types', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $primaryPlantTypeId = $this->input('primary_plant_type_id');

                if ($primaryPlantTypeId === null || ! $this->has('plant_type_ids')) {
                    return;
                }

                $plantTypeIds = collect($this->input('plant_type_ids', []))
                    ->map(fn ($plantTypeId): int => (int) $plantTypeId);

                if (! $plantTypeIds->contains((int) $primaryPlantTypeId)) {
                    $validator->errors()->add(
                        'primary_plant_type_id',
                        'The primary plant type must be included in plant_type_ids.'
                    );
                }
            },
        ];
    }
}
