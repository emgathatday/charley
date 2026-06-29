<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\User;
use Database\Factories\ConnectionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectionApiFeatureTest extends TestCase
{
    use RefreshDatabase;

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

        $this->actingAs($user)
            ->getJson('/api/v1/connections')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'requester_id',
                        'requester',
                        'receiver_id',
                        'receiver',
                        'status',
                        'initiated_context',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownConnection->id);
    }

    public function test_user_can_request_connection(): void
    {
        $requester = User::factory()->professional()->create();
        $receiver = User::factory()->professional()->create();

        $this->actingAs($requester)
            ->postJson('/api/v1/connections', [
                'receiver_id' => $receiver->id,
                'initiated_context' => 'engineer_to_engineer',
            ])
            ->assertCreated()
            ->assertJsonPath('data.requester_id', $requester->id)
            ->assertJsonPath('data.receiver_id', $receiver->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.initiated_context', 'engineer_to_engineer');

        $this->assertDatabaseHas('connections', [
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => 'pending',
        ]);
    }

    public function test_connection_request_rejects_invalid_payload(): void
    {
        $requester = User::factory()->professional()->create();

        $this->actingAs($requester)
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

        $this->actingAs($receiver)
            ->postJson("/api/v1/connections/{$acceptedConnection->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.blocked_by', null);

        $declineRequester = User::factory()->professional()->create();
        $declineReceiver = User::factory()->professional()->create();
        $declinedConnection = ConnectionFactory::new()->pending()->create([
            'requester_id' => $declineRequester->id,
            'receiver_id' => $declineReceiver->id,
        ]);

        $this->actingAs($declineReceiver)
            ->postJson("/api/v1/connections/{$declinedConnection->id}/decline")
            ->assertOk()
            ->assertJsonPath('data.status', 'declined');

        $blockRequester = User::factory()->professional()->create();
        $blockReceiver = User::factory()->professional()->create();
        $blockedConnection = ConnectionFactory::new()->accepted()->create([
            'requester_id' => $blockRequester->id,
            'receiver_id' => $blockReceiver->id,
        ]);

        $this->actingAs($blockRequester)
            ->postJson("/api/v1/connections/{$blockedConnection->id}/block")
            ->assertOk()
            ->assertJsonPath('data.status', 'blocked')
            ->assertJsonPath('data.blocked_by', $blockRequester->id);
    }

    public function test_non_receiver_cannot_accept_connection(): void
    {
        $requester = User::factory()->professional()->create();
        $receiver = User::factory()->professional()->create();
        $connection = ConnectionFactory::new()->pending()->create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
        ]);

        $this->actingAs($requester)
            ->postJson("/api/v1/connections/{$connection->id}/accept")
            ->assertStatus(500);
    }

    public function test_missing_connection_returns_not_found(): void
    {
        $user = User::factory()->professional()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/connections/999999/block')
            ->assertNotFound();
    }
}
