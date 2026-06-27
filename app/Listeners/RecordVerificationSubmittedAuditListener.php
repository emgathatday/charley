<?php

namespace App\Listeners;

use App\Events\VerificationSubmittedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RecordVerificationSubmittedAuditListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(VerificationSubmittedEvent $event): void
    {
        app(RecordIamAuditEventListener::class)->handle($event);
    }
}
