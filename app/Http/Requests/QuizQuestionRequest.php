<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string'],
            'question_image_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media_files,id'],
            'difficulty_level' => ['sometimes', 'in:easy,medium,hard'],
            'status' => ['sometimes', 'in:active,draft,archived'],
            'choices' => ['required', 'array', 'min:2'],
            'choices.*.choice_text' => ['required', 'string'],
            'choices.*.is_correct' => ['sometimes', 'boolean'],
            'choices.*.explanation' => ['sometimes', 'nullable', 'string'],
            'choices.*.sort_order' => ['sometimes', 'integer'],
        ];
    }
}
