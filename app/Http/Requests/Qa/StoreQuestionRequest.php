<?php

namespace App\Http\Requests\Qa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'posted_by_admin_id' => ['nullable', 'integer', 'exists:users,id', 'required_with:on_behalf_of_partner_id'],
            'on_behalf_of_partner_id' => ['nullable', 'integer', 'exists:partner_profiles,id'],
            'weekly_theme_id' => ['nullable', 'integer', 'exists:weekly_themes,id'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'attachment_media_ids' => ['nullable', 'array'],
            'attachment_media_ids.*' => ['integer', 'exists:media_files,id'],
            'knowledge_domain_ids' => ['nullable', 'array'],
            'knowledge_domain_ids.*' => ['integer', 'exists:knowledge_domains,id'],
            'status' => ['sometimes', Rule::in(['pending', 'published'])],
        ];
    }
}
