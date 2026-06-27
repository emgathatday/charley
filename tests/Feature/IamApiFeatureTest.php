<?php

namespace Tests\Feature;

use App\Models\LoginToken;
use App\Models\User;
use App\Models\VerificationReminderSchedule;
use App\Models\VerificationRequest;
use Database\Factories\VerificationRequestFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IamApiFeatureTest extends TestCase
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

    public function test_register_creates_user_and_returns_resource_structure(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'new_member',
            'first_name' => null,
            'last_name' => 'Member',
            'email' => 'member@example.test',
            'password' => 'password123',
            'role' => 'unverified_member',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'username',
                    'email',
                    'role',
                    'is_verified',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.username', 'new_member')
            ->assertJsonPath('data.role', 'unverified_member');

        $this->assertDatabaseHas('users', [
            'username' => 'new_member',
            'email' => 'member@example.test',
        ]);
    }

    public function test_register_rejects_invalid_payload(): void
    {
        User::factory()->create([
            'username' => 'existing_member',
            'email' => 'existing@example.test',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'existing_member',
            'email' => 'not-an-email',
            'password' => 'short',
            'role' => 'admin',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'email', 'password', 'role']);
    }

    public function test_login_returns_user_resource_and_updates_last_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.test',
            'password' => Hash::make('password123'),
            'last_login_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.test',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'email', 'last_login_at']])
            ->assertJsonPath('data.id', $user->id);

        $this->assertTrue($user->fresh()->last_login_at->equalTo(now()), 'Login should mark last login time.');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_logout_returns_success_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.logged_out', true);
    }

    public function test_issue_and_consume_login_token_flow(): void
    {
        $user = User::factory()->create(['email' => 'token@example.test']);

        $issueResponse = $this->postJson('/api/v1/auth/login-tokens', [
            'email' => 'token@example.test',
            'type' => 'otp',
            'expires_in_minutes' => 5,
        ]);

        $issueResponse->assertOk()
            ->assertJsonStructure(['data' => ['plain_token', 'login_token' => ['id', 'user_id', 'type', 'is_used', 'expires_at']]]);

        $plainToken = $issueResponse->json('data.plain_token');

        $this->postJson('/api/v1/auth/login-tokens/consume', [
            'token' => $plainToken,
            'type' => 'otp',
        ])->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.login_token.is_used', true);

        $this->assertTrue(LoginToken::firstOrFail()->is_used, 'Consumed login token should be persisted as used.');
    }

    public function test_verification_submission_requires_authentication(): void
    {
        $this->postJson('/api/v1/verification-requests', [
            'submission_type' => 'initial',
            'verification_method' => 'work_email',
        ])->assertUnauthorized();
    }

    public function test_verification_submission_creates_pending_request(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/verification-requests', [
                'submission_type' => 'initial',
                'verification_method' => 'work_email',
                'document_media_ids' => [1, 2],
                'notes' => 'Please review.',
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'user_id', 'submission_type', 'verification_method', 'status']])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_verification_submission_rejects_invalid_fields(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/verification-requests', [
                'submission_type' => 'invalid',
                'verification_method' => 'invalid',
                'document_media_ids' => [0],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['submission_type', 'verification_method', 'document_media_ids.0']);
    }

    public function test_admin_can_approve_verification_and_schedule_reminders(): void
    {
        $member = User::factory()->unverified()->create();
        $admin = User::factory()->admin()->create();
        $verificationRequest = VerificationRequestFactory::new()->create([
            'user_id' => $member->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->postJson("/api/v1/verification-requests/{$verificationRequest->id}/approve", [
                'admin_notes' => 'Approved.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reviewed_by', $admin->id);

        $this->assertSame('professional', $member->fresh()->role, 'Approval should promote user to professional.');
        $this->assertSame(4, VerificationReminderSchedule::where('user_id', $member->id)->count(), 'Approval should schedule reminder rows.');
    }

    public function test_non_admin_cannot_approve_verification(): void
    {
        $member = User::factory()->unverified()->create();
        $verificationRequest = VerificationRequestFactory::new()->create(['status' => 'pending']);

        $this->actingAs($member)
            ->postJson("/api/v1/verification-requests/{$verificationRequest->id}/approve", [
                'admin_notes' => 'Nope.',
            ])
            ->assertForbidden();
    }

    public function test_approve_missing_verification_request_returns_not_found(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/verification-requests/999999/approve', [
                'admin_notes' => 'Missing.',
            ])
            ->assertNotFound();
    }

    public function test_admin_can_reject_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $verificationRequest = VerificationRequestFactory::new()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->postJson("/api/v1/verification-requests/{$verificationRequest->id}/reject", [
                'admin_notes' => 'Need more evidence.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.admin_notes', 'Need more evidence.');
    }

    public function test_mfa_enable_rejects_short_secret(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/account/security/mfa', [
                'secret' => 'short',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['secret']);
    }

    public function test_freeze_sets_account_status_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'self_frozen_at' => null,
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/account/security/freeze')
            ->assertOk()
            ->assertJsonPath('data.status', 'frozen');

        $this->assertSame('frozen', $user->fresh()->status, 'Freeze endpoint should persist frozen status.');
    }
}
