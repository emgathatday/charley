<?php

namespace Tests\Unit;

use App\Models\Connection;
use App\Models\User;
use App\Policies\ConnectionPolicy;
use Illuminate\Auth\Access\Response;
use Tests\TestCase;

class ConnectionPolicyTest extends TestCase
{
    private ConnectionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ConnectionPolicy;
    }

    public function test_admin_before_allows_all_connection_actions(): void
    {
        $this->assertTrue($this->policy->before($this->user(['role' => 'admin']), 'block'));
    }

    public function test_accept_and_decline_allow_receiver_on_pending_connection(): void
    {
        $receiver = $this->user(['id' => 2, 'role' => 'professional', 'status' => 'active']);
        $connection = new Connection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'pending',
        ]);

        $this->assertAllowed($this->policy->accept($receiver, $connection));
        $this->assertAllowed($this->policy->decline($receiver, $connection));
    }

    public function test_accept_denies_non_receiver(): void
    {
        $connection = new Connection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'pending',
        ]);

        $this->assertDenied($this->policy->accept($this->user(['id' => 1]), $connection));
    }

    public function test_block_allows_participant_and_denies_outsider(): void
    {
        $connection = new Connection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'accepted',
        ]);

        $this->assertAllowed($this->policy->block($this->user(['id' => 1]), $connection));
        $this->assertDenied($this->policy->block($this->user(['id' => 3]), $connection));
    }

    public function test_message_only_allows_participants_on_accepted_connections(): void
    {
        $accepted = new Connection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'accepted',
        ]);
        $pending = new Connection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'pending',
        ]);

        $this->assertAllowed($this->policy->message($this->user(['id' => 1]), $accepted));
        $this->assertDenied($this->policy->message($this->user(['id' => 1]), $pending));
        $this->assertDenied($this->policy->message($this->user(['id' => 3]), $accepted));
    }

    public function test_create_enforces_direct_connection_roles(): void
    {
        $engineer = $this->user(['id' => 1, 'role' => 'professional', 'status' => 'active']);
        $otherEngineer = $this->user(['id' => 2, 'role' => 'unverified_member', 'status' => 'active']);
        $partner = $this->user(['id' => 3, 'role' => 'partner', 'status' => 'active']);

        $this->assertAllowed($this->policy->create($engineer, $otherEngineer, 'engineer_to_engineer'));
        $this->assertAllowed($this->policy->create($engineer, $partner, 'engineer_to_partner'));
        $this->assertDenied($this->policy->create($partner, $engineer, 'engineer_to_partner'));
        $this->assertDenied($this->policy->create($engineer, $partner, 'invalid'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function user(array $attributes): User
    {
        $user = new User($attributes);
        $user->id = $attributes['id'] ?? null;

        return $user;
    }

    private function assertAllowed(bool|Response $result): void
    {
        $this->assertTrue($result === true || $result->allowed());
    }

    private function assertDenied(bool|Response $result): void
    {
        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($result->denied());
    }
}
