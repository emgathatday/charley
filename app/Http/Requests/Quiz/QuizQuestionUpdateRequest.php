<?php

namespace App\Http\Requests\Quiz;

use App\Models\QuizQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizQuestionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->route('quiz')) === true;
    }

    public function rules(): array
    {
        return [
            'question_text' => ['sometimes', 'required', 'string'],
            'question_type' => ['sometimes', 'required', 'string', Rule::in(QuizQuestion::TYPES)],
            'options' => ['sometimes', 'required', 'array', 'min:2'],
            'options.*' => ['required', 'string', 'max:1000'],
            'correct_answer' => ['sometimes', 'required', 'array', 'min:1'],
            'correct_answer.*' => ['integer', 'min:0'],
            'points' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'explanation' => ['sometimes', 'nullable', 'string'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
