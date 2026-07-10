<?php

namespace App\Http\Requests\KnowledgeDomain;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'knowledge_domain_id' => ['required', 'integer', 'exists:knowledge_domains,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('quizzes', 'slug')],
            'description' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts_per_user' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(Quiz::STATUSES)],
        ];
    }
}