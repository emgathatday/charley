<?php

namespace App\Jobs;

use App\Events\FeedCacheRebuildRequested;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RebuildPersonalizedFeedCachesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public readonly string $reason = 'scheduled')
    {
        $this->onQueue('feed-cms');
    }

    public function handle(): void
    {
        event(new FeedCacheRebuildRequested(null, $this->reason));

        User::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    RebuildUserFeedCacheJob::dispatch($user->id, $this->reason)->onQueue('feed-cms');
                }
            });
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Personalized feed cache rebuild dispatch failed.', [
            'reason' => $this->reason,
            'message' => $exception?->getMessage(),
        ]);
    }
}
