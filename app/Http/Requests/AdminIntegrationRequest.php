<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'provider' => ['required', 'in:outlook,gmail'],
            'access_token' => ['required', 'string'],
            'refresh_token' => ['nullable', 'string'],
            'token_expires_at' => ['required', 'date'],
            'config_metadata' => ['nullable', 'array'],
        ];
    }
}
