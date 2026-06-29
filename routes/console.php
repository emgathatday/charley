<?php

use App\Jobs\DispatchVerificationReminderNotifications;
use App\Jobs\RebuildProfileSearchIndexJob;
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
// Rebuild profile search entries so Expert Directory stays in sync with profile privacy and discoverability.
Schedule::call(function (): void {
    try {
        RebuildProfileSearchIndexJob::dispatch();
    } catch (Throwable $exception) {
        Log::error('Unable to dispatch profile search index rebuild schedule.', [
            'message' => $exception->getMessage(),
        ]);
    }
})->daily()->name('profile-search-index-rebuild')->withoutOverlapping();
