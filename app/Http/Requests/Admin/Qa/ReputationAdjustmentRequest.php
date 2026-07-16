<?php

namespace App\Http\Requests\Admin\Qa;

use Illuminate\Foundation\Http\FormRequest;

class ReputationAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'points' => ['required', 'integer'],
            'reason' => ['required', 'string'],
        ];
    }
}
