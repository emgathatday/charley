<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminSupportTicketReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'is_internal_note' => ['nullable', 'boolean'],
        ];
    }
}
