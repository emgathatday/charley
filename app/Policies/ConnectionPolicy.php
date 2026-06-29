<?php

namespace App\Policies;

use App\Models\Connection;
use App\Models\User;

class ConnectionPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->canUseConnections($user);
    }

    public function view(User $user, Connection $connection): bool
    {
        return $this->canUseConnections($user)
            && $this->isParticipant($user, $connection);
    }

    public function create(User $user, User $receiver, ?string $context = null): bool
    {
        if (! $this->canUseConnections($user)
            || ! $this->canUseConnections($receiver)
            || (int) $user->id === (int) $receiver->id
        ) {
            return false;
        }

        if (! $this->contextAllowed($user, $receiver, $context)) {
            return false;
        }

        return ! Connection::query()
            ->whereIn('status', ['pending', 'accepted', 'blocked'])
            ->where(function ($query) use ($user, $receiver): void {
                $query->where(function ($query) use ($user, $receiver): void {
                    $query->where('requester_id', $user->id)
                        ->where('receiver_id', $receiver->id);
                })->orWhere(function ($query) use ($user, $receiver): void {
                    $query->where('requester_id', $receiver->id)
                        ->where('receiver_id', $user->id);
                });
            })
            ->exists();
    }

    public function accept(User $user, Connection $connection): bool
    {
        return $this->canUseConnections($user)
            && (int) $connection->receiver_id === (int) $user->id
            && $connection->status === 'pending';
    }

    public function decline(User $user, Connection $connection): bool
    {
        return $this->accept($user, $connection);
    }

    public function block(User $user, Connection $connection): bool
    {
        return $this->canUseConnections($user)
            && $this->isParticipant($user, $connection)
            && $connection->status !== 'blocked';
    }

    public function delete(User $user, Connection $connection): bool
    {
        return $this->canUseConnections($user)
            && $this->isParticipant($user, $connection);
    }

    private function canUseConnections(User $user): bool
    {
        return $user->status === 'active'
            && in_array($user->role, ['professional', 'partner'], true);
    }

    private function isParticipant(User $user, Connection $connection): bool
    {
        return (int) $connection->requester_id === (int) $user->id
            || (int) $connection->receiver_id === (int) $user->id;
    }

    private function contextAllowed(User $requester, User $receiver, ?string $context): bool
    {
        return match ($context) {
            'engineer_to_engineer' => $requester->role === 'professional' && $receiver->role === 'professional',
            'partner_to_engineer' => $requester->role === 'partner' && $receiver->role === 'professional',
            'engineer_to_partner' => $requester->role === 'professional' && $receiver->role === 'partner',
            default => in_array($requester->role, ['professional', 'partner'], true)
                && in_array($receiver->role, ['professional', 'partner'], true),
        };
    }
}
