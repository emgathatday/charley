<?php

use App\Jobs\DispatchVerificationReminderNotifications;
use App\Jobs\ExpireStaleFeedCacheJob;
use App\Jobs\RebuildPersonalizedFeedCachesJob;
use App\Jobs\RefreshHomepageFeedPriorityEffectsJob;
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

// Rebuild personalized homepage feed caches through queued per-user jobs.
Schedule::call(function (): void {
    try {
        RebuildPersonalizedFeedCachesJob::dispatch('scheduled')->onQueue('feed-cms');
    } catch (Throwable $exception) {
        Log::error('Unable to dispatch personalized feed cache rebuild schedule.', [
            'message' => $exception->getMessage(),
        ]);
    }
})->everyThirtyMinutes()->name('feed-cache-rebuild')->withoutOverlapping();

// Expire stale feed cache rows so homepage feed queries stay bounded.
Schedule::call(function (): void {
    try {
        ExpireStaleFeedCacheJob::dispatch()->onQueue('feed-cms');
    } catch (Throwable $exception) {
        Log::error('Unable to dispatch stale feed cache expiry schedule.', [
            'message' => $exception->getMessage(),
        ]);
    }
})->hourly()->name('feed-cache-expire-stale')->withoutOverlapping();

// Refresh priority-weight effects by rebuilding feed caches after admin priority changes.
Schedule::call(function (): void {
    try {
        RefreshHomepageFeedPriorityEffectsJob::dispatch(null, 'scheduled')->onQueue('feed-cms');
    } catch (Throwable $exception) {
        Log::error('Unable to dispatch homepage feed priority refresh schedule.', [
            'message' => $exception->getMessage(),
        ]);
    }
})->daily()->name('feed-priority-effects-refresh')->withoutOverlapping();

