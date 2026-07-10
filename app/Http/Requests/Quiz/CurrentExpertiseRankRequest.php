<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class CurrentExpertiseRankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'handbook_category_id' => ['nullable', 'integer', 'exists:handbook_categories,id'],
        ];
    }
}
