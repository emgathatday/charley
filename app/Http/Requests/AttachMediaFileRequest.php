<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->status === 'active'
            && in_array($this->user()->role, ['admin', 'professional', 'partner', 'unverified_member'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attachable_type' => ['required', 'string', 'max:255'],
            'attachable_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
