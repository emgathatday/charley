<?php

namespace App\Http\Requests\Handbook;

use Illuminate\Foundation\Http\FormRequest;

class HandbookArticlePublishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
