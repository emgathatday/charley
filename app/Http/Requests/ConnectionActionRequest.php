<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConnectionActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'initiated_context' => ['sometimes', 'required', Rule::in(['engineer_to_engineer', 'partner_to_engineer', 'engineer_to_partner'])],
        ];
    }
}
