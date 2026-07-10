<?php

namespace App\Http\Requests\Handbook;

use Illuminate\Foundation\Http\FormRequest;

class HandbookCategoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'published_only' => ['sometimes', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
