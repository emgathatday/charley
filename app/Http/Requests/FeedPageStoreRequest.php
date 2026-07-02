<?php

namespace App\Http\Requests;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeedPageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:pages,slug'],
            'content_blocks' => ['required', 'array'],
            'status' => ['nullable', 'string', Rule::in(Page::STATUSES)],
            'is_system_page' => ['nullable', 'boolean'],
            'seo_meta' => ['nullable', 'array'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
