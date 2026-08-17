<?php

namespace App\Http\Requests\Qa;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'attachment_media_ids' => ['nullable', 'array'],
            'attachment_media_ids.*' => ['integer', 'exists:media_files,id'],
        ];
    }
}
