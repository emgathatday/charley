<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxonomySyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'taggable_type' => ['required', 'string', 'max:255'],
            'taggable_id' => ['required', 'integer', 'min:1'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'mode' => ['nullable', 'string', Rule::in(['attach', 'sync', 'detach'])],
        ];
    }
}
