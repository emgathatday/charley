<?php

namespace App\Http\Requests\Admin\Qa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'attachment_media_ids' => $this->integerList('attachment_media_ids'),
            'knowledge_domain_ids' => $this->integerList('knowledge_domain_ids'),
            'is_anonymous' => $this->boolean('is_anonymous'),
            'question_mode' => $this->input('question_mode') ?: ($this->filled('on_behalf_of_partner_id') ? 'admin_on_behalf' : 'admin_seed'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'weekly_theme_id' => ['nullable', 'integer', 'exists:weekly_themes,id'],
            'on_behalf_of_partner_id' => ['nullable', 'integer', 'exists:partner_profiles,id', 'required_if:question_mode,admin_on_behalf'],
            'question_mode' => ['required', Rule::in(['community', 'admin_seed', 'admin_on_behalf'])],
            'is_anonymous' => ['boolean'],
            'status' => ['sometimes', Rule::in(['pending', 'published', 'hidden', 'flagged'])],
            'attachment_media_ids' => ['nullable', 'array'],
            'attachment_media_ids.*' => ['integer', 'distinct', 'exists:media_files,id'],
            'knowledge_domain_ids' => ['nullable', 'array'],
            'knowledge_domain_ids.*' => ['integer', 'distinct', 'exists:knowledge_domains,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('on_behalf_of_partner_id') && $this->input('question_mode') !== 'admin_on_behalf') {
                    $validator->errors()->add('question_mode', 'On-behalf questions must use admin_on_behalf mode.');
                }
            },
        ];
    }

    /**
     * @return array<int, int>|null
     */
    private function integerList(string $key): ?array
    {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            $items = $value;
        } else {
            $items = explode(',', (string) $value);
        }

        return collect($items)
            ->map(fn ($item): string => trim((string) $item))
            ->filter(fn (string $item): bool => $item !== '')
            ->map(fn (string $item): int|string => is_numeric($item) ? (int) $item : $item)
            ->values()
            ->all();
    }
}
