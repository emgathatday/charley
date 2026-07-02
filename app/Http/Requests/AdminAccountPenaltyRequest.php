<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAccountPenaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'action_type' => ['required', 'in:warning,temporary_suspension,account_freeze,unfreeze,ban,self_freeze,self_unfreeze'],
            'reason' => ['required', 'string'],
            'evidence_ref' => ['nullable', 'array'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
