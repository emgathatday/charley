<?php

namespace App\Http\Requests;

use App\Models\PartnerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $partnerProfile = $this->route('partnerProfile');
        $partnerProfileId = $partnerProfile instanceof PartnerProfile ? $partnerProfile->id : null;
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'user_id' => [$required, 'integer', 'exists:users,id', Rule::unique('partner_profiles', 'user_id')->ignore($partnerProfileId)],
            'company_name' => [$required, 'string', 'max:255'],
            'logo_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'overview' => ['nullable', 'string'],
            'partner_tier' => ['nullable', 'in:gold,diamond,platinum'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'keywords' => ['nullable', 'array'],
            'references' => ['nullable', 'array'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
            'social_links' => ['nullable', 'array'],
            'layout_template' => ['sometimes', 'in:layout_1,layout_2,layout_3'],
            'feed_highlight_enabled' => ['sometimes', 'boolean'],
            'subscription_status' => ['sometimes', 'string', 'max:255'],
            'subscription_expires_at' => ['nullable', 'date'],
            'approval_status' => ['sometimes', 'in:pending,approved,rejected,suspended'],
            'verified_at' => ['nullable', 'date'],
        ];
    }
}
