<?php

namespace App\Http\Requests;

use App\Models\LibraryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:library_categories,id'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'content_type' => ['nullable', 'string', Rule::in(LibraryItem::CONTENT_TYPES)],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
