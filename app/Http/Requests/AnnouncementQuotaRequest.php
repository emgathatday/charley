<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementQuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'period' => ['required', 'string', 'max:20'],
            'used_count' => ['sometimes', 'integer', 'min:0'],
            'quota_limit' => ['required', 'integer', 'min:0'],
        ];
    }
}
