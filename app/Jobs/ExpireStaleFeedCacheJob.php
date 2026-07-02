<?php

namespace App\Jobs;

use App\Services\FeedCms\FeedCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpireStaleFeedCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('feed-cms');
    }

    public function handle(FeedCacheService $feedCache): void
    {
        $deleted = $feedCache->expireStale();

        Log::info('Expired stale feed cache entries.', ['deleted' => $deleted]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Stale feed cache expiry failed.', [
            'message' => $exception?->getMessage(),
        ]);
    }
}
