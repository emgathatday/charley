<?php

namespace App\Http\Requests\Quiz;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'handbook_category_id' => ['nullable', 'integer', 'exists:handbook_categories,id'],
            'status' => ['nullable', 'string', Rule::in(Quiz::STATUSES)],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
