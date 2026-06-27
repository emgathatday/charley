<?php

namespace App\Listeners;

use App\Events\AccountLockedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueueAccountLockedNotificationHookListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(AccountLockedEvent $event): void
    {
        app(QueueIamNotificationHookListener::class)->handle($event);
    }
}
