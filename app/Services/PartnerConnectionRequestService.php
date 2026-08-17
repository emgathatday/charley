<?php

namespace App\Services;

use App\Events\ConnectionPending;
use App\Events\ConnectionRequestApproved;
use App\Events\ConnectionRequestRejected;
use App\Events\ConnectionRequestSubmitted;
use App\Models\Connection;
use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PartnerConnectionRequestService
{
    private const ACTIVE_REQUEST_STATUSES = ['pending', 'approved'];

    public function create(User $requester, User $targetUser, ?string $reason = null): ConnectionRequest
    {
        $this->ensureActiveUsers($requester, $targetUser);
        $this->ensureDifferentUsers($requester, $targetUser);
        $this->ensurePartnerHasActiveSubscription($requester);
        $this->ensureNoActiveDuplicate($requester, $targetUser);

        $connectionRequest = ConnectionRequest::query()->create([
            'requester_id' => $requester->id,
            'target_user_id' => $targetUser->id,
            'reason' => $reason,
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_note' => null,
            'connection_id' => null,
        ]);

        ConnectionRequestSubmitted::dispatch($connectionRequest->id, $requester->id, $targetUser->id);

        return $connectionRequest;
    }

    public function approve(ConnectionRequest $request, User $reviewer, ?string $adminNote = null): ConnectionRequest
    {
        if ($request->status !== 'pending') {
            throw new RuntimeException('Only pending connection requests can be approved.');
        }

        if ($request->connection_id !== null) {
            throw new RuntimeException('Connection request already has a generated connection.');
        }

        return DB::transaction(function () use ($request, $reviewer, $adminNote): ConnectionRequest {
            $connection = Connection::query()->create([
                'requester_id' => $request->requester_id,
                'receiver_id' => $request->target_user_id,
                'status' => 'pending',
                'initiated_context' => 'partner_to_engineer',
                'declined_at' => null,
                'accepted_at' => null,
                'blocked_at' => null,
                'blocked_by' => null,
            ]);

            $request->forceFill([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'admin_note' => $adminNote,
                'connection_id' => $connection->id,
            ])->save();

            $request = $request->refresh();

            ConnectionRequestApproved::dispatch($request->id, $request->requester_id, $request->target_user_id, $reviewer->id, $connection->id);
            ConnectionPending::dispatch($connection->id, $connection->requester_id, $connection->receiver_id);

            return $request;
        });
    }

    public function reject(ConnectionRequest $request, User $reviewer, ?string $adminNote = null): ConnectionRequest
    {
        if ($request->status !== 'pending') {
            throw new RuntimeException('Only pending connection requests can be rejected.');
        }

        $request->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'admin_note' => $adminNote,
            'connection_id' => null,
        ])->save();

        $request = $request->refresh();

        ConnectionRequestRejected::dispatch($request->id, $request->requester_id, $request->target_user_id, $reviewer->id);

        return $request;
    }

    public function cancel(ConnectionRequest $request, User $actor): ConnectionRequest
    {
        if ((int) $request->requester_id !== (int) $actor->id) {
            throw new RuntimeException('Only the requester can cancel a connection request.');
        }

        if ($request->status !== 'pending') {
            throw new RuntimeException('Only pending connection requests can be cancelled.');
        }

        $request->forceFill([
            'status' => 'cancelled',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'connection_id' => null,
        ])->save();

        return $request->refresh();
    }

    public function hasActiveDuplicate(User $requester, User $targetUser): bool
    {
        return ConnectionRequest::query()
            ->where('requester_id', $requester->id)
            ->where('target_user_id', $targetUser->id)
            ->whereIn('status', self::ACTIVE_REQUEST_STATUSES)
            ->exists();
    }

    public function hasActivePartnerSubscription(User $partner): bool
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

    private function ensureNoActiveDuplicate(User $requester, User $targetUser): void
    {
        if ($this->hasActiveDuplicate($requester, $targetUser)) {
            throw new RuntimeException('An active connection request already exists for this requester and target user.');
        }
    }

    private function ensurePartnerHasActiveSubscription(User $partner): void
    {
        if (! $this->hasActivePartnerSubscription($partner)) {
            throw new RuntimeException('Partner does not have an active subscription permission for connection requests.');
        }
    }

    private function ensureDifferentUsers(User $requester, User $targetUser): void
    {
        if ((int) $requester->id === (int) $targetUser->id) {
            throw new RuntimeException('Users cannot request connections to themselves.');
        }
    }

    private function ensureActiveUsers(User $requester, User $targetUser): void
    {
        if ($requester->status !== 'active' || $targetUser->status !== 'active') {
            throw new RuntimeException('Only active users can use connection requests.');
        }
    }
}
