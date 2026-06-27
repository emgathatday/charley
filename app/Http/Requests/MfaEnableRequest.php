<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MfaEnableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'secret' => ['required', 'string', 'min:16', 'max:255'],
        ];
    }
}
