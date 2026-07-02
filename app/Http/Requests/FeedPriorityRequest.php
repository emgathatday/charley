<?php

namespace App\Http\Requests;

use App\Models\HomepageFeedPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeedPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'content_type' => ['required', 'string', Rule::in(HomepageFeedPriority::CONTENT_TYPES)],
            'priority_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_highlighted' => ['required', 'boolean'],
            'highlight_color' => ['nullable', 'string', 'max:32'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
