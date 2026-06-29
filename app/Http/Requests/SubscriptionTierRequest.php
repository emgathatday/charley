<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tier = $this->route('subscriptionTier');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('subscription_tiers', 'name')->ignore($tier)],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'ai_monthly_limit' => ['required', 'integer', 'min:-1'],
            'announcement_frequency' => ['required', Rule::in(['weekly', 'monthly'])],
            'announcement_limit' => ['required', 'integer', 'min:0'],
            'can_host_webinar' => ['sometimes', 'boolean'],
            'can_initiate_message' => ['sometimes', 'boolean'],
            'can_create_poll' => ['sometimes', 'boolean'],
            'can_publish_events' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
