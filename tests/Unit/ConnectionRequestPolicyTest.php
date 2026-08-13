<?php

namespace Tests\Unit;

use App\Models\ConnectionRequest;
use App\Models\User;
use App\Policies\ConnectionRequestPolicy;
use Illuminate\Auth\Access\Response;
use Tests\TestCase;

class ConnectionRequestPolicyTest extends TestCase
{
    private ConnectionRequestPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ConnectionRequestPolicy;
    }

    public function test_admin_before_allows_request_review_actions(): void
    {
        $this->assertTrue($this->policy->before($this->user(['role' => 'admin']), 'approve'));
    }

    public function test_view_and_cancel_allow_requester(): void
    {
        $partner = $this->user(['id' => 10, 'role' => 'partner', 'status' => 'active']);
        $request = new ConnectionRequest([
            'requester_id' => 10,
            'target_user_id' => 20,
            'status' => 'pending',
        ]);

        $this->assertAllowed($this->policy->view($partner, $request));
        $this->assertAllowed($this->policy->cancel($partner, $request));
    }

    public function test_view_and_cancel_deny_non_requester(): void
    {
        $request = new ConnectionRequest([
            'requester_id' => 10,
            'target_user_id' => 20,
            'status' => 'pending',
        ]);

        $this->assertDenied($this->policy->view($this->user(['id' => 11]), $request));
        $this->assertDenied($this->policy->cancel($this->user(['id' => 11]), $request));
    }

    public function test_cancel_denies_non_pending_request(): void
    {
        $partner = $this->user(['id' => 10, 'role' => 'partner', 'status' => 'active']);
        $request = new ConnectionRequest([
            'requester_id' => 10,
            'target_user_id' => 20,
            'status' => 'approved',
        ]);

        $this->assertDenied($this->policy->cancel($partner, $request));
    }

    public function test_create_rejects_inactive_or_non_partner_users_before_subscription_lookup(): void
    {
        $this->assertDenied($this->policy->create($this->user([
            'id' => 1,
            'role' => 'partner',
            'status' => 'frozen',
        ])));

        $this->assertDenied($this->policy->create($this->user([
            'id' => 2,
            'role' => 'professional',
            'status' => 'active',
        ])));
    }

    public function test_non_admin_review_actions_are_denied(): void
    {
        $partner = $this->user(['id' => 10, 'role' => 'partner', 'status' => 'active']);
        $request = new ConnectionRequest([
            'requester_id' => 10,
            'target_user_id' => 20,
            'status' => 'pending',
        ]);

        $this->assertDenied($this->policy->review($partner, $request));
        $this->assertDenied($this->policy->approve($partner, $request));
        $this->assertDenied($this->policy->reject($partner, $request));
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
