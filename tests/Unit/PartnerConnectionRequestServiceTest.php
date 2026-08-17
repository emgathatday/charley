<?php

namespace Tests\Unit;

use App\Models\ConnectionRequest;
use App\Models\User;
use App\Services\PartnerConnectionRequestService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PartnerConnectionRequestServiceTest extends TestCase
{
    private PartnerConnectionRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-29 09:00:00'));
        Event::fake();
        $this->service = new PartnerConnectionRequestService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_create_rejects_inactive_users_before_database_work(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only active users can use connection requests.');

        $this->service->create(
            $this->user(['id' => 1, 'role' => 'partner', 'status' => 'frozen']),
            $this->user(['id' => 2, 'role' => 'professional', 'status' => 'active']),
            'Need collaboration'
        );
    }

    public function test_create_rejects_self_request_before_database_work(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Users cannot request connections to themselves.');

        $this->service->create(
            $this->user(['id' => 7, 'role' => 'partner', 'status' => 'active']),
            $this->user(['id' => 7, 'role' => 'professional', 'status' => 'active'])
        );
    }

    public function test_create_rejects_duplicate_active_request(): void
    {
        $service = Mockery::mock(PartnerConnectionRequestService::class)->makePartial();
        $service->shouldReceive('hasActivePartnerSubscription')->once()->andReturnTrue();
        $service->shouldReceive('hasActiveDuplicate')->once()->andReturnTrue();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An active connection request already exists for this requester and target user.');

        $service->create(
            $this->user(['id' => 1, 'role' => 'partner', 'status' => 'active']),
            $this->user(['id' => 2, 'role' => 'professional', 'status' => 'active'])
        );
    }

    public function test_reject_moves_pending_request_to_rejected(): void
    {
        $reviewer = $this->user(['id' => 9, 'role' => 'admin', 'status' => 'active']);
        $request = $this->mockConnectionRequest([
            'id' => 15,
            'requester_id' => 1,
            'target_user_id' => 2,
            'status' => 'pending',
        ]);

        $result = $this->service->reject($request, $reviewer, 'Not eligible');

        $this->assertSame($request, $result);
        $this->assertSame('rejected', $request->status);
        $this->assertSame(9, $request->reviewed_by);
        $this->assertTrue($request->reviewed_at->equalTo(now()));
        $this->assertSame('Not eligible', $request->admin_note);
        $this->assertNull($request->connection_id);
    }

    public function test_cancel_moves_pending_request_to_cancelled_for_requester(): void
    {
        $actor = $this->user(['id' => 1, 'role' => 'partner', 'status' => 'active']);
        $request = $this->mockConnectionRequest([
            'id' => 16,
            'requester_id' => 1,
            'target_user_id' => 2,
            'status' => 'pending',
            'reviewed_by' => 4,
            'reviewed_at' => now(),
            'connection_id' => 50,
        ]);

        $result = $this->service->cancel($request, $actor);

        $this->assertSame($request, $result);
        $this->assertSame('cancelled', $request->status);
        $this->assertNull($request->reviewed_by);
        $this->assertNull($request->reviewed_at);
        $this->assertNull($request->connection_id);
    }

    public function test_cancel_rejects_non_requester(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only the requester can cancel a connection request.');

        $this->service->cancel(new ConnectionRequest([
            'requester_id' => 1,
            'status' => 'pending',
        ]), $this->user(['id' => 2, 'status' => 'active']));
    }

    public function test_cancel_rejects_non_pending_request(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only pending connection requests can be cancelled.');

        $this->service->cancel(new ConnectionRequest([
            'requester_id' => 1,
            'status' => 'approved',
        ]), $this->user(['id' => 1, 'status' => 'active']));
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function mockConnectionRequest(array $attributes): ConnectionRequest
    {
        $request = Mockery::mock(ConnectionRequest::class)->makePartial();
        $request->forceFill($attributes);
        $request->id = $attributes['id'] ?? 100;
        $request->shouldReceive('save')->once()->andReturnTrue();
        $request->shouldReceive('refresh')->once()->andReturnSelf();

        return $request;
    }
}
