<?php

namespace App\Http\Requests;

use App\Models\LibraryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:library_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:library_items,slug'],
            'summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'author' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'published_year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) now()->format('Y') + 1)],
            'access_level' => ['nullable', 'string', Rule::in(LibraryItem::ACCESS_LEVELS)],
            'download_allowed' => ['nullable', 'boolean'],
            'copy_paste_disabled' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(LibraryItem::STATUSES)],
            'is_ai_trainable' => ['nullable', 'boolean'],
            'content_type' => ['required', 'string', Rule::in(LibraryItem::CONTENT_TYPES)],
            'item_type' => ['nullable', 'string', Rule::in(LibraryItem::ITEM_TYPES)],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) now()->format('Y') + 1)],
            'file_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
        ];
    }
}
