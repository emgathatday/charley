<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConnectionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'partner';
    }

    public function rules(): array
    {
        return [
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
