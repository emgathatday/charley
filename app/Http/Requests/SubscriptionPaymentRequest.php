<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'partner_subscription_id' => ['required', 'integer', 'exists:partner_subscriptions,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['sometimes', 'string', 'max:255'],
            'payment_proof_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected'])],
            'transaction_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
