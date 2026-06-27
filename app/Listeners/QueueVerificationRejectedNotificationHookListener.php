<?php

namespace App\Listeners;

use App\Events\VerificationRejectedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueueVerificationRejectedNotificationHookListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(VerificationRejectedEvent $event): void
    {
        app(QueueIamNotificationHookListener::class)->handle($event);
    }
}
