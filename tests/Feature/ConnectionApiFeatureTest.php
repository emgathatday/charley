<?php

namespace Tests\Feature;

use App\Models\ConnectionRequest;
use App\Models\User;
use Database\Factories\ConnectionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConnectionApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('auth.guards.sanctum', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        Notification::fake();
    }

    public function test_connections_require_authentication(): void
    {
        $this->getJson('/api/v1/connections')->assertUnauthorized();
    }

    public function test_user_can_list_only_their_connections(): void
    {
        $user = User::factory()->professional()->create();
        $receiver = User::factory()->professional()->create();
        $otherUser = User::factory()->professional()->create();

        $ownConnection = ConnectionFactory::new()->pending()->create([
            'requester_id' => $user->id,
            'receiver_id' => $receiver->id,
        ]);
        ConnectionFactory::new()->pending()->create([
            'requester_id' => $receiver->id,
            'receiver_id' => $otherUser->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/connections')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'requester_id',
                        'receiver_id',
                        'status',
                        'initiated_context',
                        'can_message',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownConnection->id);
    }

    public function test_user_can_request_direct_connection(): void
    {
        $requester = User::factory()->professional()->create();
        $receiver = User::factory()->professional()->create();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/connections', [
                'receiver_id' => $receiver->id,
                'initiated_context' => 'engineer_to_engineer',
            ])
            ->assertCreated()
            ->assertJsonPath('data.requester_id', $requester->id)
            ->assertJsonPath('data.receiver_id', $receiver->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.can_message', false);

        $this->assertDatabaseHas('connections', [
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => 'pending',
        ]);
    }

    public function test_connection_request_rejects_invalid_payload(): void
    {
        $requester = User::factory()->professional()->create();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/connections', [
                'receiver_id' => 999999,
                'initiated_context' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receiver_id', 'initiated_context']);
    }

    public function test_receiver_can_accept_decline_and_participant_can_block_connections(): void
    {
        $requester = User::factory()->professional()->create();
        $receiver = User::factory()->professional()->create();

        $acceptedConnection = ConnectionFactory::new()->pending()->create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->actingAs($receiver, 'sanctum')
            ->postJson("/api/v1/connections/{$acceptedConnection->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.can_message', true);

        $declineRequester = User::factory()->professional()->create();
        $declineReceiver = User::factory()->professional()->create();
        $declinedConnection = ConnectionFactory::new()->pending()->create([
            'requester_id' => $declineRequester->id,
            'receiver_id' => $declineReceiver->id,
        ]);

        $this->actingAs($declineReceiver, 'sanctum')
            ->postJson("/api/v1/connections/{$declinedConnection->id}/decline")
            ->assertOk()
            ->assertJsonPath('data.status', 'declined')
            ->assertJsonPath('data.can_message', false);

        $blockRequester = User::factory()->professional()->create();
        $blockReceiver = User::factory()->professional()->create();
        $blockedConnection = ConnectionFactory::new()->accepted()->create([
            'requester_id' => $blockRequester->id,
            'receiver_id' => $blockReceiver->id,
        ]);

        $this->actingAs($blockRequester, 'sanctum')
            ->postJson("/api/v1/connections/{$blockedConnection->id}/block")
            ->assertOk()
            ->assertJsonPath('data.status', 'blocked')
            ->assertJsonPath('data.blocked_by', $blockRequester->id)
            ->assertJsonPath('data.can_message', false);
    }

    public function test_non_receiver_cannot_accept_connection(): void
    {
        $requester = User::factory()->professional()->create();
        $receiver = User::factory()->professional()->create();
        $connection = ConnectionFactory::new()->pending()->create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->actingAs($requester, 'sanctum')
            ->postJson("/api/v1/connections/{$connection->id}/accept")
            ->assertUnprocessable();
    }

    public function test_missing_connection_returns_not_found(): void
    {
        $user = User::factory()->professional()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/connections/999999/block')
            ->assertNotFound();
    }

    public function test_messaging_eligibility_is_false_until_connection_is_accepted(): void
    {
        $requester = User::factory()->professional()->create();
        $receiver = User::factory()->professional()->create();
        $connection = ConnectionFactory::new()->pending()->create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->actingAs($requester, 'sanctum')
            ->getJson("/api/v1/connections/{$connection->id}/messaging-eligibility")
            ->assertOk()
            ->assertJsonPath('can_message', false);

        $connection->forceFill(['status' => 'accepted', 'accepted_at' => now()])->save();

        $this->actingAs($requester, 'sanctum')
            ->getJson("/api/v1/connections/{$connection->id}/messaging-eligibility")
            ->assertOk()
            ->assertJsonPath('can_message', true);
    }

    public function test_gold_platinum_and_diamond_partners_submit_admin_mediated_requests(): void
    {
        foreach (['gold', 'platinum', 'diamond'] as $tierCode) {
            $partner = User::factory()->state(['role' => 'partner', 'status' => 'active'])->create();
            $engineer = User::factory()->professional()->create();
            $this->createActivePartnerSubscription($partner, $tierCode);

            $this->actingAs($partner, 'sanctum')
                ->postJson('/api/v1/connection-requests', [
                    'target_user_id' => $engineer->id,
                    'reason' => "Request admin assistance for {$tierCode} workflow.",
                ])
                ->assertCreated()
                ->assertJsonPath('data.requester_id', $partner->id)
                ->assertJsonPath('data.target_user_id', $engineer->id)
                ->assertJsonPath('data.status', 'pending')
                ->assertJsonPath('data.connection_id', null);
        }
    }

    public function test_duplicate_active_connection_request_is_rejected(): void
    {
        $partner = User::factory()->state(['role' => 'partner', 'status' => 'active'])->create();
        $engineer = User::factory()->professional()->create();
        $this->createActivePartnerSubscription($partner, 'gold');

        $payload = [
            'target_user_id' => $engineer->id,
            'reason' => 'Need admin assistance.',
        ];

        $this->actingAs($partner, 'sanctum')->postJson('/api/v1/connection-requests', $payload)->assertCreated();

        $this->actingAs($partner, 'sanctum')
            ->postJson('/api/v1/connection-requests', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'An active connection request already exists for this requester and target user.');
    }

    public function test_admin_approval_creates_pending_connection_with_partner_requester(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = User::factory()->state(['role' => 'partner', 'status' => 'active'])->create();
        $engineer = User::factory()->professional()->create();
        $connectionRequest = $this->createConnectionRequest($partner, $engineer);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/connection-requests/{$connectionRequest->id}/approve", [
                'admin_note' => 'Approved for admin-mediated introduction.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reviewed_by', $admin->id)
            ->assertJsonPath('data.connection.status', 'pending')
            ->assertJsonPath('data.connection.requester_id', $partner->id)
            ->assertJsonPath('data.connection.receiver_id', $engineer->id);

        $this->assertDatabaseHas('connections', [
            'requester_id' => $partner->id,
            'receiver_id' => $engineer->id,
            'status' => 'pending',
            'initiated_context' => 'partner_to_engineer',
        ]);
    }

    public function test_rejection_and_cancellation_keep_connection_id_null(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = User::factory()->state(['role' => 'partner', 'status' => 'active'])->create();
        $engineer = User::factory()->professional()->create();
        $rejectedRequest = $this->createConnectionRequest($partner, $engineer);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/connection-requests/{$rejectedRequest->id}/reject", [
                'admin_note' => 'Scope is unclear.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.connection_id', null);

        $secondEngineer = User::factory()->professional()->create();
        $cancelledRequest = $this->createConnectionRequest($partner, $secondEngineer);

        $this->actingAs($partner, 'sanctum')
            ->postJson("/api/v1/connection-requests/{$cancelledRequest->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.connection_id', null);
    }

    public function test_connection_request_authorization_failures(): void
    {
        $professional = User::factory()->professional()->create();
        $engineer = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $partner = User::factory()->state(['role' => 'partner', 'status' => 'active'])->create();
        $connectionRequest = $this->createConnectionRequest($partner, $engineer);

        $this->actingAs($professional, 'sanctum')
            ->postJson('/api/v1/connection-requests', ['target_user_id' => $engineer->id])
            ->assertForbidden();

        $this->actingAs($partner, 'sanctum')
            ->getJson('/api/v1/connection-requests')
            ->assertForbidden();

        $this->actingAs($professional, 'sanctum')
            ->postJson("/api/v1/connection-requests/{$connectionRequest->id}/approve", ['admin_note' => 'No.'])
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/connection-requests/999999/reject', ['admin_note' => 'No.'])
            ->assertNotFound();
    }

    private function createConnectionRequest(User $partner, User $engineer): ConnectionRequest
    {
        return ConnectionRequest::query()->create([
            'requester_id' => $partner->id,
            'target_user_id' => $engineer->id,
            'reason' => 'Need admin assistance.',
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_note' => null,
            'connection_id' => null,
        ]);
    }

    private function createActivePartnerSubscription(User $partner, string $tierCode): void
    {
        $tierId = DB::table('subscription_tiers')->insertGetId([
            'name' => $tierCode,
            'code' => $tierCode,
            'display_name' => ucfirst($tierCode),
            'description' => ucfirst($tierCode).' tier',
            'monthly_price' => 100,
            'ai_monthly_limit' => 100,
            'announcement_frequency' => 'monthly',
            'announcement_limit' => 10,
            'can_host_webinar' => false,
            'can_initiate_message' => true,
            'can_create_poll' => false,
            'can_publish_events' => false,
            'billing_cycle' => 'monthly',
            'duration_days' => 30,
            'sort_order' => 1,
            'is_public' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('subscription_permissions')->where('key', 'messages.initiate')->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('subscription_permissions')->insertGetId([
                'key' => 'messages.initiate',
                'name' => 'Initiate messages',
                'description' => 'Allows partner connection request workflow.',
                'module' => 'messaging',
                'value_type' => 'boolean',
                'default_value' => json_encode(false),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('subscription_tier_permissions')->insert([
            'tier_id' => $tierId,
            'permission_id' => $permissionId,
            'value' => json_encode(true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('partner_subscriptions')->insert([
            'user_id' => $partner->id,
            'tier_id' => $tierId,
            'status' => 'active',
            'approved_by' => null,
            'approved_at' => now(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'auto_renew' => false,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
