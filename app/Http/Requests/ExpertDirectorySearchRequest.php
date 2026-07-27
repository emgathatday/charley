<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpertDirectorySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'search_context' => ['nullable', Rule::in(['expert_directory'])],
            'is_discoverable' => ['nullable', 'boolean'],
            'plant_type_id' => [
                'nullable',
                'integer',
                Rule::exists('plant_types', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'primary_plant_type_id' => [
                'nullable',
                'integer',
                Rule::exists('plant_types', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'job_availability' => ['nullable', Rule::in(['open', 'not_looking', 'open_to_opportunities'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
