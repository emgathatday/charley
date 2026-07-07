<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'partner_tier' => ['nullable', 'string', Rule::in(['gold', 'diamond', 'platinum'])],
            'action' => ['nullable', 'string', Rule::in(['view', 'download'])],
        ];
    }
}
