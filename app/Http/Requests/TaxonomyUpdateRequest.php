<?php

namespace App\Http\Requests;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxonomyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('tags', 'name')->ignore($this->route('tag'))],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($this->route('tag'))],
            'category' => ['nullable', 'string', Rule::in(Tag::CATEGORIES)],
        ];
    }
}
