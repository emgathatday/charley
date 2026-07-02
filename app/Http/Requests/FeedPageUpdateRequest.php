<?php

namespace App\Http\Requests;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeedPageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($this->route('page'))],
            'content_blocks' => ['sometimes', 'required', 'array'],
            'status' => ['nullable', 'string', Rule::in(Page::STATUSES)],
            'is_system_page' => ['nullable', 'boolean'],
            'seo_meta' => ['nullable', 'array'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
