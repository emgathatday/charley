<?php

namespace App\Http\Requests\KnowledgeDomain;

use App\Models\KnowledgeDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KnowledgeDomainStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('knowledge_domains', 'slug')],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(KnowledgeDomain::STATUSES)],
            'rank_tiers' => ['nullable', 'array'],
            'rank_tiers.*.name' => ['required_with:rank_tiers', 'string', 'max:255'],
            'rank_tiers.*.min_points' => ['required_with:rank_tiers', 'integer', 'min:0'],
            'rank_tiers.*.badge_icon' => ['nullable', 'string', 'max:255'],
            'rank_tiers.*.sort_order' => ['required_with:rank_tiers', 'integer', 'min:1'],
        ];
    }
}