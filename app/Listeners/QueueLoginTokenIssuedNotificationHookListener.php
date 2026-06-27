<?php

namespace App\Listeners;

use App\Events\LoginTokenIssuedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueueLoginTokenIssuedNotificationHookListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(LoginTokenIssuedEvent $event): void
    {
        app(QueueIamNotificationHookListener::class)->handle($event);
    }
}
