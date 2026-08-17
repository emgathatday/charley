<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, strlen(self::BASE32_ALPHABET) - 1)];
        }

        return $secret;
    }

    public function verify(string $secret, string $code, int $window = 1, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timestamp ??= time();
        $counter = intdiv($timestamp, 30);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->at($secret, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    public function at(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $binaryCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($truncated % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function provisioningUri(User $user, string $secret, string $issuer = 'Charley'): string
    {
        $label = rawurlencode($issuer . ':' . $user->email);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);

        return "otpauth://totp/{$label}?{$query}";
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn (): string => Str::upper(Str::random(5) . '-' . Str::random(5)))
            ->all();
    }

    public function hashRecoveryCode(string $code): string
    {
        return hash('sha256', $this->normalizeRecoveryCode($code));
    }

    public function recoveryCodeMatches(string $plainCode, string $hashedCode): bool
    {
        return hash_equals($hashedCode, $this->hashRecoveryCode($plainCode));
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return Str::upper(str_replace(' ', '', trim($code)));
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
        if ($secret === '') {
            throw new InvalidArgumentException('Invalid TOTP secret.');
        }

        $buffer = 0;
        $bitsLeft = 0;
        $decoded = '';

        foreach (str_split($secret) as $char) {
            $value = strpos(self::BASE32_ALPHABET, $char);
            if ($value === false) {
                throw new InvalidArgumentException('Invalid TOTP secret.');
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $decoded .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $decoded;
    }
}
