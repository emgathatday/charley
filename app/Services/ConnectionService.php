<?php

namespace App\Services;

use App\Events\ConnectionAccepted;
use App\Events\ConnectionBlocked;
use App\Events\ConnectionDeclined;
use App\Events\ConnectionPending;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConnectionService
{
    private const DIRECT_CONTEXTS = ['engineer_to_engineer', 'engineer_to_partner'];

    private const ENGINEER_ROLES = ['professional', 'unverified_member'];

    public function request(User $requester, User $receiver, string $context): Connection
    {
        $this->ensureActiveUsers($requester, $receiver);
        $this->ensureDifferentUsers($requester, $receiver);
        $this->ensureDirectContextAllowed($requester, $receiver, $context);

        return DB::transaction(function () use ($requester, $receiver, $context): Connection {
            $existing = $this->findExistingConnection($requester, $receiver);

            if ($existing) {
                if ($existing->status === 'blocked') {
                    throw new RuntimeException('Connection is blocked.');
                }

                return $existing;
            }

            $connection = $this->createPendingConnection($requester, $receiver, $context);

            ConnectionPending::dispatch($connection->id, $connection->requester_id, $connection->receiver_id);

            return $connection;
        });
    }

    public function createAdminMediatedPartnerConnection(User $partner, User $engineer): Connection
    {
        $this->ensureActiveUsers($partner, $engineer);
        $this->ensureDifferentUsers($partner, $engineer);

        if ($partner->role !== 'partner' || ! $this->isEngineer($engineer)) {
            throw new RuntimeException('Admin-mediated connections require a Partner requester and Engineer receiver.');
        }

        return DB::transaction(function () use ($partner, $engineer): Connection {
            $existing = $this->findExistingConnection($partner, $engineer);

            if ($existing) {
                if ($existing->status === 'blocked') {
                    throw new RuntimeException('Connection is blocked.');
                }

                return $existing;
            }

            $connection = $this->createPendingConnection($partner, $engineer, 'partner_to_engineer');

            ConnectionPending::dispatch($connection->id, $connection->requester_id, $connection->receiver_id);

            return $connection;
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

        $connection = $this->transition($connection, [
            'status' => 'accepted',
            'accepted_at' => now(),
            'declined_at' => null,
            'blocked_at' => null,
            'blocked_by' => null,
        ]);

        ConnectionAccepted::dispatch($connection->id, $connection->requester_id, $connection->receiver_id);

        return $connection;
    }

    public function decline(Connection $connection, User $actor): Connection
    {
        if ((int) $connection->receiver_id !== (int) $actor->id) {
            throw new RuntimeException('Only the receiver can decline a connection.');
        }

        if ($connection->status !== 'pending') {
            throw new RuntimeException('Only pending connections can be declined.');
        }

        $connection = $this->transition($connection, [
            'status' => 'declined',
            'declined_at' => now(),
            'accepted_at' => null,
            'blocked_at' => null,
            'blocked_by' => null,
        ]);

        ConnectionDeclined::dispatch($connection->id, $connection->requester_id, $connection->receiver_id);

        return $connection;
    }

    public function block(Connection $connection, User $actor): Connection
    {
        if (! in_array((int) $actor->id, [(int) $connection->requester_id, (int) $connection->receiver_id], true)) {
            throw new RuntimeException('Only participants can block a connection.');
        }

        if ($connection->status === 'blocked') {
            throw new RuntimeException('Connection is already blocked.');
        }

        $connection = $this->transition($connection, [
            'status' => 'blocked',
            'blocked_at' => now(),
            'blocked_by' => $actor->id,
            'accepted_at' => null,
            'declined_at' => null,
        ]);

        ConnectionBlocked::dispatch($connection->id, $connection->requester_id, $connection->receiver_id, $actor->id);

        return $connection;
    }

    public function canMessage(Connection $connection): bool
    {
        return $connection->status === 'accepted';
    }

    private function createPendingConnection(User $requester, User $receiver, string $context): Connection
    {
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
    }

    private function findExistingConnection(User $requester, User $receiver): ?Connection
    {
        return Connection::query()
            ->where(function ($query) use ($requester, $receiver): void {
                $query->where('requester_id', $requester->id)
                    ->where('receiver_id', $receiver->id);
            })
            ->orWhere(function ($query) use ($requester, $receiver): void {
                $query->where('requester_id', $receiver->id)
                    ->where('receiver_id', $requester->id);
            })
            ->first();
    }

    private function transition(Connection $connection, array $data): Connection
    {
        return DB::transaction(function () use ($connection, $data): Connection {
            $connection->forceFill($data)->save();

            return $connection->refresh();
        });
    }

    private function ensureDirectContextAllowed(User $requester, User $receiver, string $context): void
    {
        if (! in_array($context, self::DIRECT_CONTEXTS, true)) {
            throw new RuntimeException('Invalid direct connection context.');
        }

        if ($context === 'engineer_to_engineer' && (! $this->isEngineer($requester) || ! $this->isEngineer($receiver))) {
            throw new RuntimeException('Engineer to Engineer connections require two Engineer users.');
        }

        if ($context === 'engineer_to_partner' && (! $this->isEngineer($requester) || $receiver->role !== 'partner')) {
            throw new RuntimeException('Engineer to Partner connections require an Engineer requester and Partner receiver.');
        }
    }

    private function isEngineer(User $user): bool
    {
        return in_array($user->role, self::ENGINEER_ROLES, true);
    }

    private function ensureDifferentUsers(User $requester, User $receiver): void
    {
        if ((int) $requester->id === (int) $receiver->id) {
            throw new RuntimeException('Users cannot connect to themselves.');
        }
    }

    private function ensureActiveUsers(User $requester, User $receiver): void
    {
        if ($requester->status !== 'active' || $receiver->status !== 'active') {
            throw new RuntimeException('Only active users can create connections.');
        }
    }
}
