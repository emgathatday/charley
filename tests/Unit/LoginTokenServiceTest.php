<?php

namespace Tests\Unit;

use App\Models\LoginToken;
use App\Models\User;
use App\Services\LoginTokenService;
use Database\Factories\LoginTokenFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class LoginTokenServiceTest extends TestCase
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

    public function test_issue_creates_hashed_magic_link_token_with_expiry(): void
    {
        $user = User::factory()->create();

        $issued = app(LoginTokenService::class)->issue($user, 'magic_link', 20);

        $this->assertSame(64, strlen($issued['plain_token']), 'Magic link plain token should use the expected random token length.');
        $this->assertDatabaseHas('login_tokens', [
            'user_id' => $user->id,
            'token' => hash('sha256', $issued['plain_token']),
            'type' => 'magic_link',
            'is_used' => false,
        ]);
        $this->assertTrue($issued['login_token']->expires_at->equalTo(now()->addMinutes(20)), 'Token expiry should honor the requested minutes.');
    }

    public function test_issue_creates_six_digit_otp_at_minimum_expiry_boundary(): void
    {
        $user = User::factory()->create();

        $issued = app(LoginTokenService::class)->issue($user, 'otp', 1);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $issued['plain_token'], 'OTP token should be a six digit code.');
        $this->assertTrue($issued['login_token']->expires_at->equalTo(now()->addMinute()), 'OTP expiry should support the minimum one minute boundary.');
    }

    public function test_issue_rejects_invalid_token_type(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid login token type.');

        app(LoginTokenService::class)->issue(User::factory()->make(['id' => 1]), '');
    }

    public function test_issue_fails_when_user_foreign_key_is_invalid(): void
    {
        $this->expectException(QueryException::class);

        app(LoginTokenService::class)->issue(User::factory()->make(['id' => 999999]), 'email_verify');
    }

    public function test_consume_marks_valid_token_as_used(): void
    {
        $plainToken = 'valid-token';
        $loginToken = LoginTokenFactory::new()->create([
            'token' => hash('sha256', $plainToken),
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(10),
        ]);

        $consumed = app(LoginTokenService::class)->consume($plainToken, 'password_reset');

        $this->assertTrue($consumed->is($loginToken), 'Consume should return the matching token record.');
        $this->assertTrue($loginToken->fresh()->is_used, 'Consume should mark a valid token as used.');
    }

    public function test_consume_rejects_missing_token(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Login token not found.');

        app(LoginTokenService::class)->consume('missing-token', 'magic_link');
    }

    public function test_consume_rejects_used_token(): void
    {
        $plainToken = 'used-token';
        LoginTokenFactory::new()->used()->create([
            'token' => hash('sha256', $plainToken),
            'type' => 'magic_link',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Login token has already been used.');

        app(LoginTokenService::class)->consume($plainToken, 'magic_link');
    }

    public function test_consume_rejects_expired_token(): void
    {
        $plainToken = 'expired-token';
        LoginTokenFactory::new()->expired()->create([
            'token' => hash('sha256', $plainToken),
            'type' => 'magic_link',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Login token has expired.');

        app(LoginTokenService::class)->consume($plainToken, 'magic_link');
    }
}
