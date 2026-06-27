<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'role' => $this->role,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at,
            'verification_expires_at' => $this->verification_expires_at,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at,
            'mfa_enabled' => $this->mfa_enabled,
            'self_frozen_at' => $this->self_frozen_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
