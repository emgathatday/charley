<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AccountSecurityService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-25 09:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_record_failed_login_increments_attempts_without_lock_before_threshold(): void
    {
        $user = User::factory()->create([
            'login_attempts' => 2,
            'locked_until' => null,
        ]);

        $updated = app(AccountSecurityService::class)->recordFailedLogin($user, maxAttempts: 5, lockMinutes: 15);

        $this->assertSame(3, $updated->login_attempts);
        $this->assertNull($updated->locked_until);
    }

    public function test_record_failed_login_locks_account_at_threshold_boundary(): void
    {
        $user = User::factory()->create([
            'login_attempts' => 4,
            'locked_until' => null,
        ]);

        $updated = app(AccountSecurityService::class)->recordFailedLogin($user, maxAttempts: 5, lockMinutes: 30);

        $this->assertSame(5, $updated->login_attempts);
        $this->assertTrue($updated->locked_until->equalTo(now()->addMinutes(30)));
    }

    public function test_begin_mfa_setup_returns_secret_and_provisioning_uri_without_enabling(): void
    {
        $user = User::factory()->create(['mfa_enabled' => false]);

        $setup = app(AccountSecurityService::class)->beginMfaSetup($user);

        $this->assertNotEmpty($setup['secret']);
        $this->assertStringStartsWith('otpauth://totp/', $setup['provisioning_uri']);
        $this->assertStringContainsString('<svg', $setup['qr_svg']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $setup['qr_data_uri']);
        $this->assertFalse($user->fresh()->mfa_enabled);
    }

    public function test_enable_mfa_requires_valid_totp_and_hashes_recovery_codes(): void
    {
        $user = User::factory()->create([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
        ]);
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $code = $totp->at($secret, intdiv(time(), 30));

        $result = app(AccountSecurityService::class)->enableMfa($user, $secret, $code);
        $freshUser = $user->fresh();

        $this->assertCount(8, $result['recovery_codes']);
        $this->assertTrue($freshUser->mfa_enabled);
        $this->assertSame($secret, $freshUser->mfa_secret);
        $this->assertEmpty(array_intersect($result['recovery_codes'], $freshUser->mfa_recovery_codes));
        $this->assertTrue(app(AccountSecurityService::class)->validMfaCredential($freshUser, $code));
    }

    public function test_enable_mfa_rejects_invalid_totp(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create(['mfa_enabled' => false]);

        app(AccountSecurityService::class)->enableMfa($user, app(TotpService::class)->generateSecret(), '000000');
    }

    public function test_disable_mfa_consumes_recovery_code_and_clears_fields(): void
    {
        $totp = app(TotpService::class);
        $recoveryCode = 'ABCDE-12345';
        $user = User::factory()->create([
            'mfa_enabled' => true,
            'mfa_secret' => $totp->generateSecret(),
            'mfa_recovery_codes' => [$totp->hashRecoveryCode($recoveryCode)],
        ]);

        $disabled = app(AccountSecurityService::class)->disableMfa($user, recoveryCode: $recoveryCode);

        $this->assertFalse($disabled->fresh()->mfa_enabled);
        $this->assertNull($disabled->fresh()->mfa_secret);
        $this->assertNull($disabled->fresh()->mfa_recovery_codes);
    }

    public function test_freeze_sets_frozen_status_and_self_frozen_timestamp(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'self_frozen_at' => null,
        ]);

        $frozen = app(AccountSecurityService::class)->freeze($user);

        $this->assertSame('frozen', $frozen->status);
        $this->assertTrue($frozen->self_frozen_at->equalTo(now()));
    }
}
