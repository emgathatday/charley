<?php

namespace App\Http\Requests\Admin\Qa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeatureAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'confidence_level' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'admin_rank_order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
