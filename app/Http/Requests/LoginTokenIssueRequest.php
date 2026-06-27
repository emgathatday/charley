<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginTokenIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'type' => ['required', Rule::in(['magic_link', 'otp', 'email_verify', 'password_reset'])],
            'expires_in_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ];
    }
}
