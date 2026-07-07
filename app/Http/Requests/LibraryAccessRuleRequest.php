<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryAccessRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'partner_tier' => ['required', 'string', Rule::in(['gold', 'diamond', 'platinum'])],
            'can_view' => ['nullable', 'boolean'],
            'can_download' => ['nullable', 'boolean'],
            'can_copy_paste' => ['nullable', 'boolean'],
            'requires_watermark' => ['nullable', 'boolean'],
            'max_downloads_per_month' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
