<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:member_subscription_plans,id'],
            'status' => ['sometimes', Rule::in(['active', 'expired', 'cancelled'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'payment_method' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
