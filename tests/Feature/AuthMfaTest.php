<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthMfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_mfa_disabled_user_logs_in_with_existing_response_shape(): void
    {
        $user = User::factory()->create([
            'email' => 'mfa-off@example.com',
            'password' => Hash::make('password-secret'),
            'mfa_enabled' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password-secret',
        ])->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.mfa_required');
    }

    public function test_mfa_enabled_user_receives_challenge_before_login_completes(): void
    {
        $user = User::factory()->create([
            'email' => 'mfa-on@example.com',
            'password' => Hash::make('password-secret'),
            'mfa_enabled' => true,
            'mfa_secret' => app(TotpService::class)->generateSecret(),
            'last_login_at' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password-secret',
        ])->assertOk()
            ->assertJsonPath('data.mfa_required', true)
            ->assertJsonStructure(['data' => ['challenge_token']]);

        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_valid_totp_challenge_completes_login(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $user = User::factory()->create([
            'email' => 'challenge-ok@example.com',
            'password' => Hash::make('password-secret'),
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
            'last_login_at' => null,
        ]);

        $challengeToken = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password-secret',
        ])->json('data.challenge_token');

        $this->postJson('/api/v1/auth/mfa/challenge', [
            'challenge_token' => $challengeToken,
            'code' => $totp->at($secret, intdiv(time(), 30)),
        ])->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_mfa_enabled_login_with_current_totp_completes_login(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $user = User::factory()->create([
            'email' => 'login-code-ok@example.com',
            'password' => Hash::make('password-secret'),
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
            'last_login_at' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password-secret',
            'code' => $totp->at($secret, intdiv(time(), 30)),
        ])->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.mfa_required');

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_mfa_enabled_login_with_invalid_totp_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'login-code-bad@example.com',
            'password' => Hash::make('password-secret'),
            'mfa_enabled' => true,
            'mfa_secret' => app(TotpService::class)->generateSecret(),
            'last_login_at' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password-secret',
            'code' => '000000',
        ])->assertUnprocessable();

        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_invalid_totp_challenge_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'challenge-bad@example.com',
            'password' => Hash::make('password-secret'),
            'mfa_enabled' => true,
            'mfa_secret' => app(TotpService::class)->generateSecret(),
            'last_login_at' => null,
        ]);

        $challengeToken = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password-secret',
        ])->json('data.challenge_token');

        $this->postJson('/api/v1/auth/mfa/challenge', [
            'challenge_token' => $challengeToken,
            'code' => '000000',
        ])->assertUnprocessable();

        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_authenticated_mfa_setup_confirm_and_disable_flow(): void
    {
        $user = User::factory()->create(['mfa_enabled' => false]);
        $totp = app(TotpService::class);

        $setup = $this->actingAs($user)->postJson('/api/v1/account/security/mfa/setup')
            ->assertOk()
            ->assertJsonStructure(['data' => ['secret', 'provisioning_uri']])
            ->json('data');

        $confirm = $this->actingAs($user)->postJson('/api/v1/account/security/mfa', [
            'secret' => $setup['secret'],
            'code' => $totp->at($setup['secret'], intdiv(time(), 30)),
        ])->assertOk()
            ->assertJsonStructure(['data' => ['user', 'recovery_codes']])
            ->json('data');

        $this->assertTrue($user->fresh()->mfa_enabled);
        $this->assertNotContains($confirm['recovery_codes'][0], $user->fresh()->mfa_recovery_codes);

        $this->actingAs($user)->deleteJson('/api/v1/account/security/mfa', [
            'recovery_code' => $confirm['recovery_codes'][0],
        ])->assertOk();

        $this->assertFalse($user->fresh()->mfa_enabled);
        $this->assertNull($user->fresh()->mfa_secret);
        $this->assertNull($user->fresh()->mfa_recovery_codes);
    }
}
