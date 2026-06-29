<?php

namespace Tests\Unit;

use App\Models\Connection;
use App\Models\User;
use App\Services\ConnectionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ConnectionServiceTest extends TestCase
{
    private ConnectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-29 09:00:00'));
        $this->service = new ConnectionService();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_request_rejects_inactive_users(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only active users can create connections.');

        $this->service->request(
            new User(['status' => 'frozen']),
            new User(['status' => 'active']),
            'engineer_to_engineer'
        );
    }

    public function test_request_rejects_self_connection(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Users cannot connect to themselves.');

        $requester = new User(['status' => 'active']);
        $requester->id = 5;

        $receiver = new User(['status' => 'active']);
        $receiver->id = 5;

        $this->service->request($requester, $receiver, 'engineer_to_engineer');
    }

    public function test_request_rejects_invalid_context(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid connection context.');

        $requester = new User(['status' => 'active']);
        $requester->id = 5;

        $receiver = new User(['status' => 'active']);
        $receiver->id = 6;

        $this->service->request($requester, $receiver, 'invalid');
    }

    public function test_accept_moves_pending_connection_to_accepted(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());

        $actor = new User();
        $actor->id = 2;

        $connection = $this->mockTransitionConnection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'pending',
        ]);

        $updated = $this->service->accept($connection, $actor);

        $this->assertSame($connection, $updated, 'Accept should return the transitioned connection.');
        $this->assertSame('accepted', $connection->status, 'Accept should set accepted status.');
        $this->assertTrue($connection->accepted_at->equalTo(now()), 'Accept should store accepted timestamp.');
        $this->assertNull($connection->declined_at, 'Accept should clear declined timestamp.');
        $this->assertNull($connection->blocked_at, 'Accept should clear blocked timestamp.');
        $this->assertNull($connection->blocked_by, 'Accept should clear blocker.');
    }

    public function test_accept_rejects_actor_who_is_not_receiver(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only the receiver can accept a connection.');

        $actor = new User();
        $actor->id = 3;

        $connection = new Connection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'pending',
        ]);

        $this->service->accept($connection, $actor);
    }

    public function test_decline_moves_pending_connection_to_declined(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());

        $actor = new User();
        $actor->id = 2;

        $connection = $this->mockTransitionConnection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'pending',
        ]);

        $this->service->decline($connection, $actor);

        $this->assertSame('declined', $connection->status, 'Decline should set declined status.');
        $this->assertTrue($connection->declined_at->equalTo(now()), 'Decline should store declined timestamp.');
        $this->assertNull($connection->accepted_at, 'Decline should clear accepted timestamp.');
        $this->assertNull($connection->blocked_at, 'Decline should clear blocked timestamp.');
        $this->assertNull($connection->blocked_by, 'Decline should clear blocker.');
    }

    public function test_block_allows_participant_and_records_blocker(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());

        $actor = new User();
        $actor->id = 1;

        $connection = $this->mockTransitionConnection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'accepted',
        ]);

        $this->service->block($connection, $actor);

        $this->assertSame('blocked', $connection->status, 'Block should set blocked status.');
        $this->assertTrue($connection->blocked_at->equalTo(now()), 'Block should store blocked timestamp.');
        $this->assertSame(1, $connection->blocked_by, 'Block should record the actor.');
        $this->assertNull($connection->accepted_at, 'Block should clear accepted timestamp.');
        $this->assertNull($connection->declined_at, 'Block should clear declined timestamp.');
    }

    public function test_block_rejects_non_participant(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only participants can block a connection.');

        $actor = new User();
        $actor->id = 3;

        $connection = new Connection([
            'requester_id' => 1,
            'receiver_id' => 2,
            'status' => 'accepted',
        ]);

        $this->service->block($connection, $actor);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function mockTransitionConnection(array $attributes): Connection
    {
        $connection = Mockery::mock(Connection::class)->makePartial();
        $connection->forceFill($attributes);
        $connection->shouldReceive('save')->once()->andReturnTrue();
        $connection->shouldReceive('refresh')->once()->andReturnSelf();

        return $connection;
    }
}
