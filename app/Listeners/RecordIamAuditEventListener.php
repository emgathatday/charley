<?php

namespace App\Listeners;

use App\Events\AccountFrozenEvent;
use App\Events\AccountLockedEvent;
use App\Events\LoginTokenIssuedEvent;
use App\Events\UserRegisteredEvent;
use App\Events\VerificationApprovedEvent;
use App\Events\VerificationRejectedEvent;
use App\Events\VerificationSubmittedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RecordIamAuditEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @param  UserRegisteredEvent|LoginTokenIssuedEvent|VerificationSubmittedEvent|VerificationApprovedEvent|VerificationRejectedEvent|AccountLockedEvent|AccountFrozenEvent  $event
     */
    public function handle(object $event): void
    {
        Log::info('IAM domain event observed.', [
            'event' => $event::class,
            'payload' => $this->payload($event),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(object $event): array
    {
        return match (true) {
            $event instanceof UserRegisteredEvent => [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ],
            $event instanceof LoginTokenIssuedEvent => [
                'user_id' => $event->user->id,
                'login_token_id' => $event->loginToken->id,
                'type' => $event->loginToken->type,
            ],
            $event instanceof VerificationSubmittedEvent => [
                'verification_request_id' => $event->verificationRequest->id,
                'user_id' => $event->verificationRequest->user_id,
                'status' => $event->verificationRequest->status,
            ],
            $event instanceof VerificationApprovedEvent, $event instanceof VerificationRejectedEvent => [
                'verification_request_id' => $event->verificationRequest->id,
                'user_id' => $event->verificationRequest->user_id,
                'reviewer_id' => $event->reviewer->id,
                'status' => $event->verificationRequest->status,
            ],
            $event instanceof AccountLockedEvent, $event instanceof AccountFrozenEvent => [
                'user_id' => $event->user->id,
                'reason' => $event->reason,
            ],
            default => [],
        };
    }
}
