<?php

namespace App\Listeners;

use App\Events\ConnectionAccepted;
use App\Events\ConnectionBlocked;
use App\Events\ConnectionDeclined;
use App\Events\ConnectionPending;
use App\Events\ConnectionRequestApproved;
use App\Events\ConnectionRequestRejected;
use App\Events\ConnectionRequestSubmitted;
use App\Models\Connection;
use App\Models\ConnectionRequest;
use App\Models\User;
use App\Notifications\ConnectionWorkflowNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

class SendConnectionWorkflowNotifications implements ShouldQueue
{
    public int $tries = 3;

    public int $timeout = 60;

    public string $queue = 'notifications';

    public function handle(
        ConnectionRequestSubmitted|ConnectionRequestApproved|ConnectionRequestRejected|ConnectionPending|ConnectionAccepted|ConnectionDeclined|ConnectionBlocked $event,
    ): void {
        match (true) {
            $event instanceof ConnectionRequestSubmitted => $this->requestSubmitted($event),
            $event instanceof ConnectionRequestApproved => $this->requestApproved($event),
            $event instanceof ConnectionRequestRejected => $this->requestRejected($event),
            $event instanceof ConnectionPending => $this->connectionPending($event),
            $event instanceof ConnectionAccepted => $this->connectionAccepted($event),
            $event instanceof ConnectionDeclined => $this->connectionDeclined($event),
            $event instanceof ConnectionBlocked => $this->connectionBlocked($event),
        };
    }

    private function requestSubmitted(ConnectionRequestSubmitted $event): void
    {
        $request = ConnectionRequest::query()->find($event->connectionRequestId);
        if (! $request) {
            return;
        }

        $this->notifyUsers(
            User::query()->where('role', 'admin')->get()->push($request->requester)->filter(),
            'Connection request submitted',
            'A Partner connection request is waiting for admin review.',
            url('/dashboard/profiles/connection-requests'),
            ['connection_request_id' => $request->id, 'status' => $request->status]
        );
    }

    private function requestApproved(ConnectionRequestApproved $event): void
    {
        $request = ConnectionRequest::query()->with(['requester', 'targetUser'])->find($event->connectionRequestId);
        if (! $request) {
            return;
        }

        $this->notifyUsers(
            collect([$request->requester, $request->targetUser])->filter(),
            'Connection request approved',
            'Admin approved the request and created a pending connection. Messaging stays disabled until accepted.',
            url('/dashboard/profiles/connection-requests'),
            ['connection_request_id' => $request->id, 'connection_id' => $event->connectionId, 'status' => 'approved']
        );
    }

    private function requestRejected(ConnectionRequestRejected $event): void
    {
        $request = ConnectionRequest::query()->with('requester')->find($event->connectionRequestId);
        if (! $request) {
            return;
        }

        $this->notifyUsers(
            collect([$request->requester])->filter(),
            'Connection request rejected',
            'Admin rejected the connection request. No connection was created.',
            url('/dashboard/profiles/connection-requests'),
            ['connection_request_id' => $request->id, 'status' => 'rejected']
        );
    }

    private function connectionPending(ConnectionPending $event): void
    {
        $connection = Connection::query()->with(['requester', 'receiver'])->find($event->connectionId);
        if (! $connection) {
            return;
        }

        $this->notifyUsers(
            collect([$connection->receiver])->filter(),
            'Pending connection request',
            'A connection is pending your acceptance. Messaging is disabled until you accept.',
            url('/dashboard'),
            ['connection_id' => $connection->id, 'status' => 'pending']
        );
    }

    private function connectionAccepted(ConnectionAccepted $event): void
    {
        $this->notifyConnectionParticipants($event->connectionId, 'Connection accepted', 'This connection is accepted. Messaging is now available.', 'accepted');
    }

    private function connectionDeclined(ConnectionDeclined $event): void
    {
        $this->notifyConnectionParticipants($event->connectionId, 'Connection declined', 'This connection was declined. Messaging remains unavailable.', 'declined');
    }

    private function connectionBlocked(ConnectionBlocked $event): void
    {
        $this->notifyConnectionParticipants($event->connectionId, 'Connection blocked', 'This connection was blocked. Messaging is unavailable.', 'blocked');
    }

    private function notifyConnectionParticipants(int $connectionId, string $title, string $body, string $status): void
    {
        $connection = Connection::query()->with(['requester', 'receiver'])->find($connectionId);
        if (! $connection) {
            return;
        }

        $this->notifyUsers(
            collect([$connection->requester, $connection->receiver])->filter(),
            $title,
            $body,
            url('/dashboard'),
            ['connection_id' => $connection->id, 'status' => $status]
        );
    }

    private function notifyUsers(Collection $users, string $title, string $body, string $url, array $context): void
    {
        $users->unique('id')
            ->filter(fn (User $user): bool => $this->allowsConnectionNotifications($user))
            ->each(fn (User $user) => $user->notify(new ConnectionWorkflowNotification($title, $body, $url, $context)));
    }

    private function allowsConnectionNotifications(User $user): bool
    {
        $preferences = $user->notification_preferences ?? $user->engineerProfile?->notification_preferences ?? null;

        if (! is_array($preferences)) {
            return true;
        }

        return (bool) ($preferences['connections'] ?? $preferences['connection_workflow'] ?? true);
    }
}
