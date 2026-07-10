<?php

namespace App\Http\Requests\Quiz;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;

class QuizAttemptSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $quiz instanceof Quiz
            ? $this->user()?->can('attempt', $quiz) === true
            : $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
        ];
    }
}
