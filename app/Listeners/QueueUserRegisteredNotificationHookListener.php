<?php

namespace App\Listeners;

use App\Events\UserRegisteredEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueueUserRegisteredNotificationHookListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(UserRegisteredEvent $event): void
    {
        app(QueueIamNotificationHookListener::class)->handle($event);
    }
}
