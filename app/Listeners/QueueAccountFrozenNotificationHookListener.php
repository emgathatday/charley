<?php

namespace App\Listeners;

use App\Events\AccountFrozenEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueueAccountFrozenNotificationHookListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(AccountFrozenEvent $event): void
    {
        app(QueueIamNotificationHookListener::class)->handle($event);
    }
}
