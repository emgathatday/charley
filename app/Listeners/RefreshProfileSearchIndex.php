<?php

namespace App\Listeners;

use App\Events\ConnectionUpdated;
use App\Events\ProfileUpdated;
use App\Jobs\RebuildProfileSearchIndexJob;
use App\Models\EngineerProfile;
use App\Models\UnverifiedMemberProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshProfileSearchIndex implements ShouldQueue
{
    public int $tries = 3;

    public int $timeout = 60;

    public string $queue = 'search-index';

    public function handle(ProfileUpdated|ConnectionUpdated $event): void
    {
        if ($event instanceof ProfileUpdated) {
            RebuildProfileSearchIndexJob::dispatch($event->profileClass, $event->profileId);

            return;
        }

        $this->dispatchUserProfiles($event->requesterId);
        $this->dispatchUserProfiles($event->receiverId);
    }

    public function failed(ProfileUpdated|ConnectionUpdated $event, Throwable $exception): void
    {
        Log::error('Unable to refresh profile search index from event.', [
            'event' => $event::class,
            'message' => $exception->getMessage(),
        ]);
    }

    private function dispatchUserProfiles(int $userId): void
    {
        EngineerProfile::query()
            ->where('user_id', $userId)
            ->select('id')
            ->each(fn (EngineerProfile $profile) => RebuildProfileSearchIndexJob::dispatch(EngineerProfile::class, $profile->id));

        UnverifiedMemberProfile::query()
            ->where('user_id', $userId)
            ->select('id')
            ->each(fn (UnverifiedMemberProfile $profile) => RebuildProfileSearchIndexJob::dispatch(UnverifiedMemberProfile::class, $profile->id));
    }
}