<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountSecurityService
{
    public function recordFailedLogin(User $user, int $maxAttempts = 5, int $lockMinutes = 15): User
    {
        return DB::transaction(function () use ($user, $maxAttempts, $lockMinutes): User {
            $attempts = $user->login_attempts + 1;

            $user->forceFill([
                'login_attempts' => $attempts,
                'locked_until' => $attempts >= $maxAttempts ? now()->addMinutes($lockMinutes) : null,
            ])->save();

            return $user;
        });
    }

    public function enableMfa(User $user, string $secret): array
    {
        return DB::transaction(function () use ($user, $secret): array {
            $recoveryCodes = collect(range(1, 8))
                ->map(fn (): string => Str::upper(Str::random(10)))
                ->all();

            $user->forceFill([
                'mfa_enabled' => true,
                'mfa_secret' => $secret,
                'mfa_recovery_codes' => array_map(fn (string $code): string => hash('sha256', $code), $recoveryCodes),
            ])->save();

            return $recoveryCodes;
        });
    }

    public function freeze(User $user): User
    {
        $user->forceFill([
            'status' => 'frozen',
            'self_frozen_at' => now(),
        ])->save();

        return $user;
    }
}
