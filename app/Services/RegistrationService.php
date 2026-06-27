<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            return User::create([
                'username' => $data['username'],
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'unverified_member',
                'is_verified' => false,
                'verified_at' => null,
                'verification_expires_at' => null,
                'status' => 'active',
                'last_login_at' => null,
                'login_attempts' => 0,
                'locked_until' => null,
                'mfa_enabled' => false,
                'mfa_secret' => null,
                'mfa_recovery_codes' => null,
                'self_frozen_at' => null,
            ]);
        });
    }

    public function markLoggedIn(User $user): User
    {
        $user->forceFill([
            'last_login_at' => now(),
            'login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        return $user;
    }
}
