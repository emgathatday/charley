<?php

namespace App\Http\Requests\Quiz;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Quiz::class) === true;
    }

    public function rules(): array
    {
        return [
            'handbook_category_id' => ['nullable', 'integer', 'exists:handbook_categories,id'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:quizzes,slug'],
            'description' => ['nullable', 'string'],
            'passing_score_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'target_expertise_level_id' => ['nullable', 'integer', 'exists:expertise_levels,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'max_attempts_per_user' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(Quiz::STATUSES)],
        ];
    }
}
