<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AccountSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

        $this->assertSame(3, $updated->login_attempts, 'Failed login should increment attempts.');
        $this->assertNull($updated->locked_until, 'Account should stay unlocked before threshold.');
    }

    public function test_record_failed_login_locks_account_at_threshold_boundary(): void
    {
        $user = User::factory()->create([
            'login_attempts' => 4,
            'locked_until' => null,
        ]);

        $updated = app(AccountSecurityService::class)->recordFailedLogin($user, maxAttempts: 5, lockMinutes: 30);

        $this->assertSame(5, $updated->login_attempts, 'Failed login should reach the threshold.');
        $this->assertTrue($updated->locked_until->equalTo(now()->addMinutes(30)), 'Account should lock until the configured time.');
    }

    public function test_enable_mfa_sets_secret_and_hashes_recovery_codes(): void
    {
        $user = User::factory()->create([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
        ]);

        $recoveryCodes = app(AccountSecurityService::class)->enableMfa($user, 'secret-value');
        $freshUser = $user->fresh();

        $this->assertCount(8, $recoveryCodes, 'MFA should return exactly eight recovery codes.');
        $this->assertTrue($freshUser->mfa_enabled, 'MFA should be enabled.');
        $this->assertSame('secret-value', $freshUser->mfa_secret, 'MFA secret should be stored.');
        $this->assertSame(array_map(fn (string $code): string => hash('sha256', $code), $recoveryCodes), $freshUser->mfa_recovery_codes, 'Stored recovery codes should be hashed versions of returned codes.');
        $this->assertEmpty(array_intersect($recoveryCodes, $freshUser->mfa_recovery_codes), 'Plain recovery codes should not be stored.');
    }

    public function test_enable_mfa_accepts_empty_secret_edge_case(): void
    {
        $user = User::factory()->create(['mfa_secret' => null]);

        app(AccountSecurityService::class)->enableMfa($user, '');

        $this->assertSame('', $user->fresh()->mfa_secret, 'Empty secret should be persisted exactly when provided.');
    }

    public function test_freeze_sets_frozen_status_and_self_frozen_timestamp(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'self_frozen_at' => null,
        ]);

        $frozen = app(AccountSecurityService::class)->freeze($user);

        $this->assertSame('frozen', $frozen->status, 'Freeze should set account status to frozen.');
        $this->assertTrue($frozen->self_frozen_at->equalTo(now()), 'Freeze should record the self frozen timestamp.');
    }
}
