<?php

namespace App\Http\Requests\KnowledgeDomain;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $quiz = $this->route('quiz');

        return [
            'knowledge_domain_id' => ['sometimes', 'required', 'integer', 'exists:knowledge_domains,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('quizzes', 'slug')->ignore($quiz?->id)],
            'description' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts_per_user' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(Quiz::STATUSES)],
        ];
    }
}