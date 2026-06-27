<?php

use App\Jobs\DispatchVerificationReminderNotifications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dispatch due professional verification expiry reminders to the database queue.
Schedule::call(function (): void {
    try {
        DispatchVerificationReminderNotifications::dispatch()->onQueue('notifications');
    } catch (Throwable $exception) {
        Log::error('Unable to dispatch verification reminder schedule.', [
            'message' => $exception->getMessage(),
        ]);
    }
})->hourly()->name('verification-reminder-dispatch')->withoutOverlapping();

