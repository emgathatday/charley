<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartnerMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'user_id' => [$required, 'integer', 'exists:users,id'],
            'member_role' => [$required, 'in:manager,staff,viewer'],
            'joined_at' => [$required, 'date'],
            'status' => [$required, 'in:active,inactive'],
        ];
    }
}
