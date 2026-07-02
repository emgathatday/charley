<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PageRevisionRollbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [];
    }
}
