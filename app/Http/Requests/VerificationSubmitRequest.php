<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerificationSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_type' => ['required', Rule::in(['initial', 'renewal', 'resubmission'])],
            'verification_method' => ['required', Rule::in(['work_email', 'linkedin', 'company_letter', 'university_letter', 'justification_letter'])],
            'document_media_ids' => ['nullable', 'array'],
            'document_media_ids.*' => ['integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
