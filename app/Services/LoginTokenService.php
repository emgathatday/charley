<?php

namespace App\Services;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LoginTokenService
{
    public function issue(User $user, string $type, int $expiresInMinutes = 30): array
    {
        if (! in_array($type, ['magic_link', 'otp', 'email_verify', 'password_reset'], true)) {
            throw new RuntimeException('Invalid login token type.');
        }

        return DB::transaction(function () use ($user, $type, $expiresInMinutes): array {
            $plainToken = $type === 'otp'
                ? (string) random_int(100000, 999999)
                : Str::random(64);

            $loginToken = LoginToken::create([
                'user_id' => $user->id,
                'token' => hash('sha256', $plainToken),
                'type' => $type,
                'is_used' => false,
                'expires_at' => now()->addMinutes($expiresInMinutes),
                'created_at' => now(),
            ]);

            return [
                'plain_token' => $plainToken,
                'login_token' => $loginToken,
            ];
        });
    }

    public function consume(string $plainToken, string $type): LoginToken
    {
        return DB::transaction(function () use ($plainToken, $type): LoginToken {
            $loginToken = LoginToken::query()
                ->where('token', hash('sha256', $plainToken))
                ->where('type', $type)
                ->first();

            if (! $loginToken) {
                throw new RuntimeException('Login token not found.');
            }

            if ($loginToken->is_used) {
                throw new RuntimeException('Login token has already been used.');
            }

            if ($loginToken->expires_at->isPast()) {
                throw new RuntimeException('Login token has expired.');
            }

            $loginToken->forceFill(['is_used' => true])->save();

            return $loginToken;
        });
    }
}
