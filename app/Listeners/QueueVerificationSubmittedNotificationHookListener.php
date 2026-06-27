<?php

namespace App\Listeners;

use App\Events\VerificationSubmittedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueueVerificationSubmittedNotificationHookListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(VerificationSubmittedEvent $event): void
    {
        app(QueueIamNotificationHookListener::class)->handle($event);
    }
}
