<?php

namespace App\Http\Requests\KnowledgeDomain;

use Illuminate\Foundation\Http\FormRequest;

class DomainRankTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'min_points' => ['required', 'integer', 'min:0'],
            'badge_icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:1'],
        ];
    }
}