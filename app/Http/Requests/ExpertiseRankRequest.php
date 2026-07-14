<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpertiseRankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'rank_tier_id' => ['sometimes', 'integer', 'exists:expertise_rank_tiers,id'],
            'knowledge_domain_id' => ['sometimes', 'integer', 'exists:knowledge_domains,id'],
            'plant_type_id' => ['sometimes', 'integer', 'exists:plant_types,id'],
            'self_rated_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'status' => ['sometimes', Rule::in(['active', 'draft', 'deleted'])],
            'is_active' => ['sometimes', 'boolean'],
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
