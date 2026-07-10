<?php

namespace App\Http\Requests\KnowledgeDomain;

use Illuminate\Foundation\Http\FormRequest;

class QuizAttemptSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
        ];
    }
}