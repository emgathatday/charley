<?php

namespace App\Http\Requests\KnowledgeDomain;

use App\Models\QuizQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string'],
            'question_type' => ['nullable', Rule::in(QuizQuestion::TYPES)],
            'options' => ['required', 'array', 'min:2'],
            'correct_answer' => ['required'],
            'points' => ['nullable', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}