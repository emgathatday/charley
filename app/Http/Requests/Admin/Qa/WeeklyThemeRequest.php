<?php

namespace App\Http\Requests\Admin\Qa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WeeklyThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'week_start_date' => ['required', 'date'],
            'week_end_date' => ['required', 'date', 'after_or_equal:week_start_date'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ];
    }
}
