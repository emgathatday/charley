<?php

namespace Tests\Feature;

use App\Models\AdminIntegration;
use App\Models\ContentApprovalQueue;
use App\Models\PlatformSetting;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminOperationsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_operations_tables_include_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('support_tickets', [
            'id',
            'user_id',
            'subject',
            'category',
            'priority',
            'status',
            'description',
            'assigned_to',
            'resolved_at',
        ]));
        $this->assertTrue(Schema::hasColumns('support_ticket_replies', [
            'id',
            'ticket_id',
            'sender_id',
            'content',
            'is_internal_note',
        ]));
        $this->assertTrue(Schema::hasColumns('platform_settings', ['id', 'key', 'value', 'group', 'description']));
        $this->assertTrue(Schema::hasColumns('admin_integrations', [
            'id',
            'user_id',
            'provider',
            'access_token',
            'refresh_token',
            'token_expires_at',
            'config_metadata',
        ]));
        $this->assertTrue(Schema::hasColumns('content_approval_queue', [
            'id',
            'approvable_type',
            'approvable_id',
            'submitted_by',
            'content_title',
            'content_type_label',
            'status',
            'assigned_to',
            'reviewed_by',
        ]));
    }

    public function test_admin_operation_api_routes_require_authentication_and_admin_role(): void
    {
        $user = User::factory()->professional()->create();

        $this->getJson('/api/v1/support-tickets')->assertUnauthorized();
        $this->getJson('/api/v1/platform-settings')->assertUnauthorized();
        $this->getJson('/api/v1/admin-integrations')->assertUnauthorized();
        $this->getJson('/api/v1/content-approvals')->assertUnauthorized();

        $this->actingAs($user)->getJson('/api/v1/support-tickets')->assertForbidden();
        $this->actingAs($user)->getJson('/api/v1/platform-settings')->assertForbidden();
        $this->actingAs($user)->getJson('/api/v1/admin-integrations')->assertForbidden();
        $this->actingAs($user)->getJson('/api/v1/content-approvals')->assertForbidden();
    }

    public function test_admin_can_manage_support_ticket_flow_through_api(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->professional()->create();

        $ticketId = $this->actingAs($admin)
            ->postJson('/api/v1/support-tickets', [
                'user_id' => $member->id,
                'subject' => 'Cannot update subscription',
                'category' => 'subscription_support',
                'priority' => null,
                'description' => 'The billing screen is stuck.',
                'assigned_to' => null,
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'user_id', 'subject', 'priority', 'status', 'description']])
            ->assertJsonPath('data.user_id', $member->id)
            ->assertJsonPath('data.priority', 'normal')
            ->json('data.id');

        $this->actingAs($admin)
            ->postJson("/api/v1/support-tickets/{$ticketId}/assign", ['admin_id' => $admin->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_to', $admin->id);

        $this->actingAs($admin)
            ->postJson("/api/v1/support-tickets/{$ticketId}/replies", [
                'content' => 'We are checking the billing provider.',
                'is_internal_note' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.ticket_id', $ticketId)
            ->assertJsonPath('data.is_internal_note', true);

        $this->actingAs($admin)
            ->postJson("/api/v1/support-tickets/{$ticketId}/resolve", ['content' => 'Resolved by admin.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('support_ticket_replies', [
            'ticket_id' => $ticketId,
            'sender_id' => $admin->id,
            'content' => 'Resolved by admin.',
            'is_internal_note' => true,
        ]);
    }

    public function test_support_ticket_api_validates_payloads_missing_resources_and_fk_constraints(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->createSupportTicket(User::factory()->professional()->create());

        $this->actingAs($admin)
            ->postJson('/api/v1/support-tickets', [
                'user_id' => 999999,
                'subject' => '',
                'category' => 'bad-category',
                'priority' => 'bad-priority',
                'description' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'subject', 'category', 'priority', 'description']);

        $this->actingAs($admin)->getJson('/api/v1/support-tickets/999999')->assertNotFound();

        $this->expectException(QueryException::class);
        SupportTicketReply::query()->create([
            'ticket_id' => $ticket->id,
            'sender_id' => 999999,
            'content' => 'Invalid sender.',
            'is_internal_note' => false,
        ]);
    }

    public function test_admin_can_update_platform_settings_and_enforces_unique_keys(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = $this->createPlatformSetting(['key' => 'support.sla_hours']);

        $settingId = $this->actingAs($admin)
            ->postJson('/api/v1/platform-settings', [
                'key' => 'notifications.digest',
                'value' => 'enabled',
                'group' => 'notification',
                'description' => null,
            ])
            ->assertCreated()
            ->assertJsonPath('data.value', 'enabled')
            ->json('data.id');

        $this->actingAs($admin)
            ->putJson("/api/v1/platform-settings/{$settingId}", [
                'key' => 'notifications.digest',
                'value' => 'disabled',
                'group' => 'notification',
                'description' => 'Nullable description can later be filled.',
            ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Nullable description can later be filled.');

        $this->actingAs($admin)
            ->postJson('/api/v1/platform-settings', [
                'key' => $existing->key,
                'value' => 'duplicate',
                'group' => 'support',
            ])
            ->assertOk();
    }

    public function test_admin_can_manage_integrations_and_hidden_tokens_are_not_returned(): void
    {
        $admin = User::factory()->admin()->create();

        $integrationId = $this->actingAs($admin)
            ->postJson('/api/v1/admin-integrations', [
                'provider' => 'gmail',
                'access_token' => 'secret-access-token',
                'refresh_token' => null,
                'token_expires_at' => now()->addDay()->toDateTimeString(),
                'config_metadata' => ['label' => 'Support mailbox'],
            ])
            ->assertCreated()
            ->assertJsonMissing(['access_token' => 'secret-access-token'])
            ->assertJsonPath('data.provider', 'gmail')
            ->json('data.id');

        $this->actingAs($admin)
            ->postJson('/api/v1/admin-integrations', [
                'provider' => 'bad-provider',
                'access_token' => '',
                'token_expires_at' => 'not-a-date',
                'config_metadata' => 'bad-metadata',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider', 'access_token', 'token_expires_at', 'config_metadata']);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin-integrations/{$integrationId}")
            ->assertOk()
            ->assertJsonPath('data.id', $integrationId);

        $this->assertDatabaseMissing('admin_integrations', ['id' => $integrationId]);
    }

    public function test_admin_can_review_content_approval_items(): void
    {
        $admin = User::factory()->admin()->create();
        $approval = $this->createContentApproval(User::factory()->professional()->create());

        $this->actingAs($admin)
            ->getJson('/api/v1/content-approvals')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'content_title', 'status', 'submitted_by']]]);

        $this->actingAs($admin)
            ->postJson("/api/v1/content-approvals/{$approval->id}/assign", ['admin_id' => $admin->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_to', $admin->id);

        $this->actingAs($admin)
            ->postJson("/api/v1/content-approvals/{$approval->id}/approve", ['admin_notes' => null])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reviewed_by', $admin->id);

        $this->actingAs($admin)
            ->postJson("/api/v1/content-approvals/{$approval->id}/reject", ['admin_notes' => 'Already reviewed.'])
            ->assertServerError();
    }

    public function test_admin_operations_web_routes_require_admin_access_and_render_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $this->createSupportTicket($user);
        $this->createPlatformSetting();
        $this->createAdminIntegration($admin);
        $this->createContentApproval($user);

        $this->get('/dashboard/admin-operations')->assertRedirect('/login');

        $this->actingAs($user)
            ->get('/dashboard/admin-operations')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/dashboard/admin-operations')
            ->assertOk()
            ->assertSee('Admin Operations')
            ->assertSee('Support tickets')
            ->assertSee('Platform settings')
            ->assertSee('Integrations')
            ->assertSee('Content approval queue');
    }

    private function createSupportTicket(User $user, array $attributes = []): SupportTicket
    {
        return SupportTicket::query()->create(array_merge([
            'user_id' => $user->id,
            'subject' => 'Support request',
            'category' => 'technical_issue',
            'priority' => 'normal',
            'status' => 'open',
            'description' => 'Support request body.',
            'assigned_to' => null,
            'resolved_at' => null,
        ], $attributes));
    }

    private function createPlatformSetting(array $attributes = []): PlatformSetting
    {
        return PlatformSetting::query()->create(array_merge([
            'key' => 'support.routing',
            'value' => 'enabled',
            'group' => 'support',
            'description' => null,
        ], $attributes));
    }

    private function createAdminIntegration(User $admin, array $attributes = []): AdminIntegration
    {
        return AdminIntegration::query()->create(array_merge([
            'user_id' => $admin->id,
            'provider' => 'outlook',
            'access_token' => 'token',
            'refresh_token' => null,
            'token_expires_at' => now()->addDay(),
            'config_metadata' => null,
        ], $attributes));
    }

    private function createContentApproval(User $submitter, array $attributes = []): ContentApprovalQueue
    {
        return ContentApprovalQueue::query()->create(array_merge([
            'approvable_type' => 'post',
            'approvable_id' => 1,
            'submitted_by' => $submitter->id,
            'submitter_tier' => null,
            'content_title' => 'Safety guide',
            'content_type_label' => 'Article',
            'priority' => 'normal',
            'status' => 'pending',
            'assigned_to' => null,
            'admin_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'submitted_at' => now(),
        ], $attributes));
    }
}
