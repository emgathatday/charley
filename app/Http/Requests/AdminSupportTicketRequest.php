<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:subscription_support,technical_issue,content_approval,account_issue,other'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'description' => ['required', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
