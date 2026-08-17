<?php

namespace App\Policies;

use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class ConnectionRequestPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool|Response
    {
        return $user->role === 'partner'
            ? true
            : Response::deny('Only Partners can list their connection requests.');
    }

    public function view(User $user, ConnectionRequest $connectionRequest): bool|Response
    {
        return (int) $connectionRequest->requester_id === (int) $user->id
            ? true
            : Response::deny('Only the requester can view this connection request.');
    }

    public function create(User $user): bool|Response
    {
        if ($user->status !== 'active') {
            return Response::deny('Only active users can create connection requests.');
        }

        if ($user->role !== 'partner') {
            return Response::deny('Only Partners can create admin-mediated connection requests.');
        }

        return $this->hasActivePartnerSubscription($user)
            ? true
            : Response::deny('Partner does not have an active subscription permission for connection requests.');
    }

    public function cancel(User $user, ConnectionRequest $connectionRequest): bool|Response
    {
        if ((int) $connectionRequest->requester_id !== (int) $user->id) {
            return Response::deny('Only the requester can cancel this connection request.');
        }

        return $connectionRequest->status === 'pending'
            ? true
            : Response::deny('Only pending connection requests can be cancelled.');
    }

    public function review(User $user, ConnectionRequest $connectionRequest): bool|Response
    {
        return Response::deny('Only admins can review connection requests.');
    }

    public function approve(User $user, ConnectionRequest $connectionRequest): bool|Response
    {
        return $this->review($user, $connectionRequest);
    }

    public function reject(User $user, ConnectionRequest $connectionRequest): bool|Response
    {
        return $this->review($user, $connectionRequest);
    }

    private function hasActivePartnerSubscription(User $partner): bool
    {
        return DB::table('partner_subscriptions')
            ->where('user_id', $partner->id)
            ->whereIn('status', ['active', 'approved'])
            ->whereNull('cancelled_at')
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('subscription_tier_permissions')
                    ->join('subscription_permissions', 'subscription_permissions.id', '=', 'subscription_tier_permissions.permission_id')
                    ->whereColumn('subscription_tier_permissions.tier_id', 'partner_subscriptions.tier_id')
                    ->where('subscription_permissions.key', 'messages.initiate')
                    ->where('subscription_permissions.is_active', true);
            })
            ->exists();
    }
}
