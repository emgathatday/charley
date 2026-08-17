<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IamAccountPenaltyFreezeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_account_penalty_freeze_list(): void
    {
        $this->get(route('admin.dashboard.iam.account-penalty-freeze'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_is_blocked_from_account_penalty_freeze_list(): void
    {
        $member = User::factory()->professional()->create(['status' => 'active']);

        $this->actingAs($member)
            ->get(route('admin.dashboard.iam.account-penalty-freeze'))
            ->assertForbidden();
    }

    public function test_admin_can_render_account_penalty_freeze_list_with_confirmed_penalty_data(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $target = User::factory()->professional()->create([
            'first_name' => 'Penalty',
            'last_name' => 'Member',
            'email' => 'penalty.member@example.test',
            'status' => 'frozen',
            'mfa_enabled' => true,
            'login_attempts' => 2,
        ]);

        DB::table('account_penalties')->insert([
            'user_id' => $target->id,
            'action_type' => 'warning',
            'reason' => 'Unauthorized advertising in Q&A answer',
            'evidence_ref' => json_encode(['source' => 'moderation']),
            'duration_days' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
            'admin_id' => $admin->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        DB::table('account_penalties')->insert([
            'user_id' => $target->id,
            'action_type' => 'temporary_suspension',
            'reason' => 'Temporary suspension for repeat posting',
            'evidence_ref' => json_encode(['source' => 'moderation-repeat']),
            'duration_days' => 7,
            'starts_at' => now()->subHours(12),
            'ends_at' => now()->addDays(6),
            'admin_id' => $admin->id,
            'created_at' => now()->subHours(12),
            'updated_at' => now()->subHours(12),
        ]);
        DB::table('verification_requests')->insert([
            'user_id' => $target->id,
            'submission_type' => 'initial',
            'verification_method' => 'company_letter',
            'document_media_ids' => json_encode([]),
            'notes' => 'latest security queue item',
            'status' => 'pending',
            'admin_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.account-penalty-freeze'));

        $response->assertOk()
            ->assertViewIs('iam.user-security')
            ->assertSee('Account Penalty &amp; Freeze', false)
            ->assertSee('Penalty Member')
            ->assertSee('penalty.member@example.test')
            ->assertSee('Unauthorized advertising in Q&amp;A answer', false)
            ->assertSee(route('admin.dashboard.iam.account-penalty-freeze.show', $target), false)
            ->assertDontSee('mfa_secret')
            ->assertDontSee('mfa_recovery_codes')
            ->assertDontSee('payload');

        $this->assertSame(2, substr_count($response->getContent(), 'penalty.member@example.test'));
    }

    public function test_admin_can_render_account_penalty_freeze_detail_with_penalty_history(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $target = User::factory()->professional()->create([
            'first_name' => 'Frozen',
            'last_name' => 'Expert',
            'email' => 'frozen.expert@example.test',
            'status' => 'frozen',
            'login_attempts' => 3,
            'locked_until' => now()->addHour(),
            'mfa_enabled' => true,
            'self_frozen_at' => now()->subDays(2),
        ]);

        DB::table('account_penalties')->insert([
            'user_id' => $target->id,
            'action_type' => 'account_freeze',
            'reason' => 'Repeated impersonation report',
            'evidence_ref' => json_encode(['case' => 'SEC-42']),
            'duration_days' => null,
            'starts_at' => now()->subDays(2),
            'ends_at' => null,
            'admin_id' => $admin->id,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.account-penalty-freeze.show', $target));

        $response->assertOk()
            ->assertViewIs('iam.user-security-detail')
            ->assertSee('Frozen Expert')
            ->assertSee('frozen.expert@example.test')
            ->assertSee('Repeated impersonation report')
            ->assertSee('Penalty History')
            ->assertSee('Login Attempts')
            ->assertSee('3')
            ->assertDontSee('mfa_secret')
            ->assertDontSee('mfa_recovery_codes')
            ->assertDontSee('session payload')
            ->assertSee('Display-only preview');
    }

    public function test_missing_account_penalty_freeze_detail_returns_404(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);

        $this->actingAs($admin)
            ->get('/dashboard/iam/account-penalty-freeze/999999')
            ->assertNotFound();
    }

    public function test_account_penalty_freeze_update_validates_role_and_status(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $target = User::factory()->professional()->create(['status' => 'active']);

        $this->actingAs($admin)
            ->from(route('admin.dashboard.iam.account-penalty-freeze.show', $target))
            ->put(route('admin.dashboard.iam.account-penalty-freeze.update', $target), [
                'role' => 'owner',
                'status' => 'disabled',
                'admin_note' => str_repeat('x', 1001),
            ])
            ->assertRedirect(route('admin.dashboard.iam.account-penalty-freeze.show', $target))
            ->assertSessionHasErrors(['role', 'status', 'admin_note']);
    }

    public function test_account_penalty_freeze_update_preserves_confirmed_self_frozen_behavior(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $target = User::factory()->professional()->create([
            'status' => 'active',
            'self_frozen_at' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.dashboard.iam.account-penalty-freeze.update', $target), [
                'role' => 'professional',
                'status' => 'frozen',
                'admin_note' => 'Confirmed freeze reason',
            ])
            ->assertRedirect();

        $target->refresh();
        $this->assertSame('frozen', $target->status);
        $this->assertNotNull($target->self_frozen_at);

        $this->actingAs($admin)
            ->put(route('admin.dashboard.iam.account-penalty-freeze.update', $target), [
                'role' => 'professional',
                'status' => 'active',
                'admin_note' => 'Restore access',
            ])
            ->assertRedirect();

        $target->refresh();
        $this->assertSame('active', $target->status);
        $this->assertNull($target->self_frozen_at);
    }

    public function test_account_penalty_freeze_does_not_expose_unconfirmed_penalty_write_routes(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $target = User::factory()->professional()->create(['status' => 'frozen']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.account-penalty-freeze.show', $target));

        $response->assertOk()
            ->assertDontSee('account_penalties/store')
            ->assertDontSee('account-penalties/create')
            ->assertDontSee('name="evidence_ref"', false)
            ->assertDontSee('name="duration_days"', false)
;
    }

    public function test_legacy_user_security_routes_are_retired(): void
    {
        $routeNames = collect(app('router')->getRoutes())->map->getName()->filter()->values();
        $routeUris = collect(app('router')->getRoutes())->map->uri()->values();

        $this->assertFalse($routeNames->contains('admin.dashboard.iam.user-security'));
        $this->assertFalse($routeNames->contains('admin.dashboard.iam.user-security.update'));
        $this->assertFalse($routeUris->contains('dashboard/iam/user-security/{user?}'));
        $this->assertTrue($routeNames->contains('admin.dashboard.iam.account-penalty-freeze'));
        $this->assertTrue($routeNames->contains('admin.dashboard.iam.account-penalty-freeze.show'));
        $this->assertTrue($routeNames->contains('admin.dashboard.iam.account-penalty-freeze.update'));
    }
}