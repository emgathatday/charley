<?php

namespace App\Console\Commands;

use App\Jobs\ExpireStaleFeedCacheJob;
use App\Jobs\RebuildPersonalizedFeedCachesJob;
use App\Jobs\RebuildUserFeedCacheJob;
use App\Jobs\RefreshHomepageFeedPriorityEffectsJob;
use Illuminate\Console\Command;

class FeedCacheCommand extends Command
{
    protected $signature = 'feed:cache {action=rebuild : rebuild|expire|refresh-priorities} {--user= : Rebuild a single user feed cache}';

    protected $description = 'Dispatch Feed CMS cache maintenance jobs.';

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        $userId = $this->option('user');

        match ($action) {
            'rebuild' => $userId
                ? RebuildUserFeedCacheJob::dispatch((int) $userId, 'console')->onQueue('feed-cms')
                : RebuildPersonalizedFeedCachesJob::dispatch('console')->onQueue('feed-cms'),
            'expire' => ExpireStaleFeedCacheJob::dispatch()->onQueue('feed-cms'),
            'refresh-priorities' => RefreshHomepageFeedPriorityEffectsJob::dispatch(null, 'console')->onQueue('feed-cms'),
            default => $this->fail("Unsupported feed cache action [{$action}]."),
        };

        $this->info("Feed cache [{$action}] job dispatched.");

        return self::SUCCESS;
    }
}
