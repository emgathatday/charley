<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConnectionService
{
    public function request(User $requester, User $receiver, string $context): Connection
    {
        $this->ensureActiveUsers($requester, $receiver);

        if ((int) $requester->id === (int) $receiver->id) {
            throw new RuntimeException('Users cannot connect to themselves.');
        }

        if (! in_array($context, ['engineer_to_engineer', 'partner_to_engineer', 'engineer_to_partner'], true)) {
            throw new RuntimeException('Invalid connection context.');
        }

        return DB::transaction(function () use ($requester, $receiver, $context): Connection {
            $existing = Connection::query()
                ->where(function ($query) use ($requester, $receiver): void {
                    $query->where('requester_id', $requester->id)
                        ->where('receiver_id', $receiver->id);
                })
                ->orWhere(function ($query) use ($requester, $receiver): void {
                    $query->where('requester_id', $receiver->id)
                        ->where('receiver_id', $requester->id);
                })
                ->first();

            if ($existing) {
                if ($existing->status === 'blocked') {
                    throw new RuntimeException('Connection is blocked.');
                }

                return $existing;
            }

            return Connection::query()->create([
                'requester_id' => $requester->id,
                'receiver_id' => $receiver->id,
                'status' => 'pending',
                'initiated_context' => $context,
                'declined_at' => null,
                'accepted_at' => null,
                'blocked_at' => null,
                'blocked_by' => null,
            ]);
        });
    }

    public function accept(Connection $connection, User $actor): Connection
    {
        if ((int) $connection->receiver_id !== (int) $actor->id) {
            throw new RuntimeException('Only the receiver can accept a connection.');
        }

        if ($connection->status !== 'pending') {
            throw new RuntimeException('Only pending connections can be accepted.');
        }

        return $this->transition($connection, [
            'status' => 'accepted',
            'accepted_at' => now(),
            'declined_at' => null,
            'blocked_at' => null,
            'blocked_by' => null,
        ]);
    }

    public function decline(Connection $connection, User $actor): Connection
    {
        if ((int) $connection->receiver_id !== (int) $actor->id) {
            throw new RuntimeException('Only the receiver can decline a connection.');
        }

        if ($connection->status !== 'pending') {
            throw new RuntimeException('Only pending connections can be declined.');
        }

        return $this->transition($connection, [
            'status' => 'declined',
            'declined_at' => now(),
            'accepted_at' => null,
            'blocked_at' => null,
            'blocked_by' => null,
        ]);
    }

    public function block(Connection $connection, User $actor): Connection
    {
        if (! in_array((int) $actor->id, [(int) $connection->requester_id, (int) $connection->receiver_id], true)) {
            throw new RuntimeException('Only participants can block a connection.');
        }

        return $this->transition($connection, [
            'status' => 'blocked',
            'blocked_at' => now(),
            'blocked_by' => $actor->id,
            'accepted_at' => null,
            'declined_at' => null,
        ]);
    }

    private function transition(Connection $connection, array $data): Connection
    {
        return DB::transaction(function () use ($connection, $data): Connection {
            $connection->forceFill($data)->save();

            return $connection->refresh();
        });
    }

    private function ensureActiveUsers(User $requester, User $receiver): void
    {
        if ($requester->status !== 'active' || $receiver->status !== 'active') {
            throw new RuntimeException('Only active users can create connections.');
        }
    }
}
