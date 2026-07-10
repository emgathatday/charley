<?php

namespace App\Http\Requests\Handbook;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HandbookRelatedItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'relatable_type' => ['required', 'string', 'max:255'],
            'relatable_id' => ['required', 'integer', 'min:1'],
            'relation_type' => ['required', Rule::in(['calculation_tool', 'library_item', 'partner_presentation', 'ai_shortcut'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
