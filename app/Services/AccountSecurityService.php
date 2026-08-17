<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountSecurityService
{
    public function __construct(private readonly TotpService $totpService)
    {
    }

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

    public function beginMfaSetup(User $user, ?string $secret = null): array
    {
        $secret = $secret ?: $this->totpService->generateSecret();

        $provisioningUri = $this->totpService->provisioningUri($user, $secret);
        $qrSvg = $this->qrSvg($provisioningUri);

        return [
            'secret' => $secret,
            'provisioning_uri' => $provisioningUri,
            'qr_svg' => $qrSvg,
            'qr_data_uri' => 'data:image/svg+xml;base64,'.base64_encode($qrSvg),
        ];
    }

    private function qrSvg(string $contents): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220, 4),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($contents);
    }

    public function enableMfa(User $user, string $secret, string $code): array
    {
        if (! $this->totpService->verify($secret, $code)) {
            throw ValidationException::withMessages(['code' => 'The MFA code is invalid.']);
        }

        return DB::transaction(function () use ($user, $secret): array {
            $recoveryCodes = $this->totpService->generateRecoveryCodes();

            $user->forceFill([
                'mfa_enabled' => true,
                'mfa_secret' => $secret,
                'mfa_recovery_codes' => array_map(fn (string $code): string => $this->totpService->hashRecoveryCode($code), $recoveryCodes),
            ])->save();

            return [
                'recovery_codes' => $recoveryCodes,
            ];
        });
    }

    public function disableMfa(User $user, ?string $code = null, ?string $recoveryCode = null): User
    {
        if (! $this->validMfaCredential($user, $code, $recoveryCode, consumeRecoveryCode: true)) {
            throw ValidationException::withMessages(['code' => 'A valid MFA code or recovery code is required.']);
        }

        return DB::transaction(function () use ($user): User {
            $user->forceFill([
                'mfa_enabled' => false,
                'mfa_secret' => null,
                'mfa_recovery_codes' => null,
            ])->save();

            return $user;
        });
    }

    public function validMfaCredential(User $user, ?string $code = null, ?string $recoveryCode = null, bool $consumeRecoveryCode = false): bool
    {
        if ($code && $user->mfa_secret && $this->totpService->verify($user->mfa_secret, $code)) {
            return true;
        }

        if (! $recoveryCode) {
            return false;
        }

        $hashedCodes = collect($user->mfa_recovery_codes ?? []);
        $matched = $hashedCodes->first(fn (string $hashedCode): bool => $this->totpService->recoveryCodeMatches($recoveryCode, $hashedCode));
        if (! $matched) {
            return false;
        }

        if ($consumeRecoveryCode) {
            DB::transaction(function () use ($user, $matched, $hashedCodes): void {
                $user->forceFill([
                    'mfa_recovery_codes' => $hashedCodes->reject(fn (string $hashedCode): bool => hash_equals($hashedCode, $matched))->values()->all(),
                ])->save();
            });
        }

        return true;
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
