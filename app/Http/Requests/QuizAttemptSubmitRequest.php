<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizAttemptSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'integer', 'exists:quiz_question_choices,id'],
        ];
    }
}
