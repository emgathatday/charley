<?php

namespace App\Http\Requests\KnowledgeDomain;

use App\Models\KnowledgeDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KnowledgeDomainUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $domain = $this->route('knowledgeDomain');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('knowledge_domains', 'slug')->ignore($domain?->id)],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(KnowledgeDomain::STATUSES)],
        ];
    }
}