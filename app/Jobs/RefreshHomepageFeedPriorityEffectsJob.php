<?php

namespace App\Jobs;

use App\Events\HomepageFeedPriorityEffectsRefreshRequested;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshHomepageFeedPriorityEffectsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public readonly ?string $contentType = null, public readonly string $reason = 'priority_refresh')
    {
        $this->onQueue('feed-cms');
    }

    public function handle(): void
    {
        event(new HomepageFeedPriorityEffectsRefreshRequested($this->contentType, $this->reason));

        RebuildPersonalizedFeedCachesJob::dispatch($this->reason)->onQueue('feed-cms');
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Homepage feed priority refresh failed.', [
            'content_type' => $this->contentType,
            'reason' => $this->reason,
            'message' => $exception?->getMessage(),
        ]);
    }
}
