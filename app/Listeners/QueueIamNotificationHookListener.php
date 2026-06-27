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

class QueueIamNotificationHookListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @param  UserRegisteredEvent|LoginTokenIssuedEvent|VerificationSubmittedEvent|VerificationApprovedEvent|VerificationRejectedEvent|AccountLockedEvent|AccountFrozenEvent  $event
     */
    public function handle(object $event): void
    {
        Log::info('IAM notification hook queued.', [
            'event' => $event::class,
        ]);
    }
}
