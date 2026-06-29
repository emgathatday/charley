<?php

namespace App\Http\Requests;

use App\Models\PartnerPresentation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerPresentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $presentation = $this->route('partnerPresentation');
        $presentationId = $presentation instanceof PartnerPresentation ? $presentation->id : null;
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'title' => [$required, 'string', 'max:255'],
            'slug' => [$required, 'string', 'max:255', Rule::unique('partner_presentations', 'slug')->ignore($presentationId)],
            'description' => ['nullable', 'string'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'equipment_category' => ['nullable', 'string', 'max:255'],
            'page_count' => ['nullable', 'integer', 'min:0'],
            'download_allowed' => ['sometimes', 'boolean'],
            'view_count' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:pending_approval,approved,rejected'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'rejection_reason' => ['nullable', 'string'],
            'is_ai_trainable' => ['sometimes', 'boolean'],
            'file_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
        ];
    }
}
