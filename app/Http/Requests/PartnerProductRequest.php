<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartnerProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'item_type' => ['sometimes', 'in:product,service,technology'],
            'description' => ['nullable', 'string'],
            'image_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'datasheet_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'keywords' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
