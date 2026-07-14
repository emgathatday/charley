<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LibraryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:library_categories,id'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'content' => ['sometimes', 'nullable', 'string'],
            'plant_type_id' => ['sometimes', 'nullable', 'integer', 'exists:plant_types,id'],
            'author' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'published_year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:2100'],
            'access_level' => ['sometimes', 'in:public,professional_only,partner_only,gold,diamond,platinum'],
            'download_allowed' => ['sometimes', 'boolean'],
            'copy_paste_disabled' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'is_ai_trainable' => ['sometimes', 'boolean'],
            'content_type' => ['sometimes', 'in:article,video,document,presentation,case_study,safety_bulletin'],
            'item_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'file_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media_files,id'],
        ];
    }
}
