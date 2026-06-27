<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialAccountLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_name' => ['required', Rule::in(['google', 'apple', 'linkedin'])],
            'provider_id' => ['required', 'string', 'max:255'],
        ];
    }
}
