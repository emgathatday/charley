<?php

namespace App\Http\Requests\KnowledgeDomain;

use App\Models\LibraryItemHotspot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryItemHotspotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'knowledge_domain_id' => ['required', 'integer', 'exists:knowledge_domains,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'shape_type' => ['nullable', Rule::in(LibraryItemHotspot::SHAPES)],
            'coordinates' => ['required', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}