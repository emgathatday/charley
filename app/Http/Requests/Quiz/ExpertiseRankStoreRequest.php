<?php

namespace App\Http\Requests\Quiz;

use App\Models\UserExpertiseRank;
use Illuminate\Foundation\Http\FormRequest;

class ExpertiseRankStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assign', UserExpertiseRank::class) === true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'expertise_level_id' => ['required', 'integer', 'exists:expertise_levels,id'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'handbook_category_id' => ['nullable', 'integer', 'exists:handbook_categories,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
