<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminContentApprovalTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'admin_notes' => ['nullable', 'string'],
        ];
    }
}
