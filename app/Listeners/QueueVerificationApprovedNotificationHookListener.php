<?php

namespace App\Listeners;

use App\Events\VerificationApprovedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueueVerificationApprovedNotificationHookListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(VerificationApprovedEvent $event): void
    {
        app(QueueIamNotificationHookListener::class)->handle($event);
    }
}
