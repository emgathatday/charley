<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AdminProfileSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_revoke_own_non_current_session(): void
    {
        $admin = $this->adminUser();
        $originalRememberToken = $admin->remember_token;
        $currentSessionId = 'current-session-id';
        $otherSessionId = 'other-session-id';
        $this->sessionRow($currentSessionId, $admin->id, 1700000100);
        $this->sessionRow($otherSessionId, $admin->id);

        Session::setId($currentSessionId);

        $this->withCookie(config('session.cookie'), $currentSessionId)
            ->withSession(['_token' => 'test-token'])
            ->actingAs($admin)
            ->call('DELETE', route('admin.dashboard.iam.users.admin-profile.sessions.revoke', $otherSessionId), [], [], [], [
                'HTTP_X_CSRF_TOKEN' => 'test-token',
            ])
            ->assertRedirect(route('admin.dashboard.iam.users.admin-profile', ['section' => 'sessions']));

        $this->assertDatabaseMissing('sessions', ['id' => $otherSessionId]);
        $this->assertDatabaseHas('sessions', ['id' => $currentSessionId, 'user_id' => $admin->id]);
        $this->assertNotSame($originalRememberToken, $admin->fresh()->remember_token);
    }

    public function test_admin_cannot_revoke_current_session(): void
    {
        $admin = $this->adminUser();
        $originalRememberToken = $admin->remember_token;
        $currentSessionId = 'current-session-id';
        $this->sessionRow($currentSessionId, $admin->id, 1700000100);

        Session::setId($currentSessionId);

        $this->withCookie(config('session.cookie'), $currentSessionId)
            ->withSession(['_token' => 'test-token'])
            ->actingAs($admin)
            ->call('DELETE', route('admin.dashboard.iam.users.admin-profile.sessions.revoke', $currentSessionId), [], [], [], [
                'HTTP_X_CSRF_TOKEN' => 'test-token',
            ])
            ->assertRedirect(route('admin.dashboard.iam.users.admin-profile', ['section' => 'sessions']));

        $this->assertDatabaseHas('sessions', ['id' => $currentSessionId, 'user_id' => $admin->id]);
        $this->assertSame($originalRememberToken, $admin->fresh()->remember_token);
    }

    public function test_admin_cannot_revoke_another_users_session(): void
    {
        $admin = $this->adminUser();
        $originalRememberToken = $admin->remember_token;
        $otherUser = User::factory()->create();
        $currentSessionId = 'current-session-id';
        $foreignSessionId = 'foreign-session-id';
        $this->sessionRow($currentSessionId, $admin->id, 1700000100);
        $this->sessionRow($foreignSessionId, $otherUser->id);

        Session::setId($currentSessionId);

        $this->withCookie(config('session.cookie'), $currentSessionId)
            ->withSession(['_token' => 'test-token'])
            ->actingAs($admin)
            ->call('DELETE', route('admin.dashboard.iam.users.admin-profile.sessions.revoke', $foreignSessionId), [], [], [], [
                'HTTP_X_CSRF_TOKEN' => 'test-token',
            ])
            ->assertRedirect(route('admin.dashboard.iam.users.admin-profile', ['section' => 'sessions']));

        $this->assertDatabaseHas('sessions', ['id' => $foreignSessionId, 'user_id' => $otherUser->id]);
        $this->assertSame($originalRememberToken, $admin->fresh()->remember_token);
    }

    public function test_admin_can_revoke_all_other_sessions_preserving_current(): void
    {
        $admin = $this->adminUser();
        $originalRememberToken = $admin->remember_token;
        $otherUser = User::factory()->create();
        $currentSessionId = 'current-session-id';
        $ownSessionA = 'own-session-a';
        $ownSessionB = 'own-session-b';
        $foreignSessionId = 'foreign-session-id';
        $this->sessionRow($currentSessionId, $admin->id, 1700000100);
        $this->sessionRow($ownSessionA, $admin->id);
        $this->sessionRow($ownSessionB, $admin->id);
        $this->sessionRow($foreignSessionId, $otherUser->id);

        Session::setId($currentSessionId);

        $this->withCookie(config('session.cookie'), $currentSessionId)
            ->withSession(['_token' => 'test-token'])
            ->actingAs($admin)
            ->call('DELETE', route('admin.dashboard.iam.users.admin-profile.sessions.revoke-others'), [], [], [], [
                'HTTP_X_CSRF_TOKEN' => 'test-token',
            ])
            ->assertRedirect(route('admin.dashboard.iam.users.admin-profile', ['section' => 'sessions']));

        $this->assertDatabaseHas('sessions', ['id' => $currentSessionId, 'user_id' => $admin->id]);
        $this->assertDatabaseMissing('sessions', ['id' => $ownSessionA]);
        $this->assertDatabaseMissing('sessions', ['id' => $ownSessionB]);
        $this->assertDatabaseHas('sessions', ['id' => $foreignSessionId, 'user_id' => $otherUser->id]);
        $this->assertNotSame($originalRememberToken, $admin->fresh()->remember_token);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password-secret'),
            'remember_token' => 'original-remember-token',
        ]);
    }

    private function sessionRow(string $id, int $userId, int $lastActivity = 1700000000): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature Test Browser',
            'payload' => base64_encode('test'),
            'last_activity' => $lastActivity,
        ]);
    }
}
