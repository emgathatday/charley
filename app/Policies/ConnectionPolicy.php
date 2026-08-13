<?php

namespace App\Policies;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConnectionPolicy
{
    private const ENGINEER_ROLES = ['professional', 'unverified_member'];

    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool|Response
    {
        return $user->status === 'active'
            ? true
            : Response::deny('Only active users can view connections.');
    }

    public function view(User $user, Connection $connection): bool|Response
    {
        return $this->isParticipant($user, $connection)
            ? true
            : Response::deny('Only connection participants can view this connection.');
    }

    public function create(User $user, User $receiver, ?string $context = null): bool|Response
    {
        if ($user->status !== 'active' || $receiver->status !== 'active') {
            return Response::deny('Only active users can create connections.');
        }

        if ((int) $user->id === (int) $receiver->id) {
            return Response::deny('Users cannot connect to themselves.');
        }

        return match ($context) {
            'engineer_to_engineer' => $this->isEngineer($user) && $this->isEngineer($receiver)
                ? true
                : Response::deny('Engineer to Engineer connections require two Engineer users.'),
            'engineer_to_partner' => $this->isEngineer($user) && $receiver->role === 'partner'
                ? true
                : Response::deny('Engineer to Partner connections require an Engineer requester and Partner receiver.'),
            default => Response::deny('Invalid direct connection context.'),
        };
    }

    public function accept(User $user, Connection $connection): bool|Response
    {
        if ((int) $connection->receiver_id !== (int) $user->id) {
            return Response::deny('Only the receiver can accept this connection.');
        }

        return $connection->status === 'pending'
            ? true
            : Response::deny('Only pending connections can be accepted.');
    }

    public function decline(User $user, Connection $connection): bool|Response
    {
        if ((int) $connection->receiver_id !== (int) $user->id) {
            return Response::deny('Only the receiver can decline this connection.');
        }

        return $connection->status === 'pending'
            ? true
            : Response::deny('Only pending connections can be declined.');
    }

    public function block(User $user, Connection $connection): bool|Response
    {
        if (! $this->isParticipant($user, $connection)) {
            return Response::deny('Only participants can block this connection.');
        }

        return $connection->status !== 'blocked'
            ? true
            : Response::deny('Connection is already blocked.');
    }

    public function message(User $user, Connection $connection): bool|Response
    {
        if (! $this->isParticipant($user, $connection)) {
            return Response::deny('Only participants can message through this connection.');
        }

        return $connection->status === 'accepted'
            ? true
            : Response::deny('Messaging is only available for accepted connections.');
    }

    private function isParticipant(User $user, Connection $connection): bool
    {
        return (int) $connection->requester_id === (int) $user->id
            || (int) $connection->receiver_id === (int) $user->id;
    }

    private function isEngineer(User $user): bool
    {
        return in_array($user->role, self::ENGINEER_ROLES, true);
    }
}
