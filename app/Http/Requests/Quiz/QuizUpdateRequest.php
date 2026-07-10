<?php

namespace App\Http\Requests\Quiz;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->route('quiz')) === true;
    }

    public function rules(): array
    {
        $quizId = $this->route('quiz')?->id;

        return [
            'handbook_category_id' => ['sometimes', 'nullable', 'integer', 'exists:handbook_categories,id'],
            'plant_type_id' => ['sometimes', 'nullable', 'integer', 'exists:plant_types,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('quizzes', 'slug')->ignore($quizId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'passing_score_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'target_expertise_level_id' => ['sometimes', 'nullable', 'integer', 'exists:expertise_levels,id'],
            'time_limit_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1440'],
            'max_attempts_per_user' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(Quiz::STATUSES)],
        ];
    }
}
