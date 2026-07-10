<?php

namespace App\Http\Requests\Quiz;

use App\Models\QuizQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizQuestionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->route('quiz')) === true;
    }

    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'string', Rule::in(QuizQuestion::TYPES)],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string', 'max:1000'],
            'correct_answer' => ['required', 'array', 'min:1'],
            'correct_answer.*' => ['integer', 'min:0'],
            'points' => ['nullable', 'integer', 'min:1', 'max:100'],
            'explanation' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
