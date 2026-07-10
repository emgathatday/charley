<?php

namespace App\Http\Requests\Handbook;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HandbookArticleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'published' => ['sometimes', 'boolean'],
            'category_id' => ['nullable', 'integer', 'exists:handbook_categories,id'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'ai_trainable' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
