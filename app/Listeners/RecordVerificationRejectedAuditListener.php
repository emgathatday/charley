<?php

namespace App\Listeners;

use App\Events\VerificationRejectedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RecordVerificationRejectedAuditListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(VerificationRejectedEvent $event): void
    {
        app(RecordIamAuditEventListener::class)->handle($event);
    }
}
