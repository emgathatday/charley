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
            'secret' => ['nullable', 'string', 'min:16', 'max:255'],
            'code' => ['required_with:secret', 'string', 'size:6'],
        ];
    }
}
