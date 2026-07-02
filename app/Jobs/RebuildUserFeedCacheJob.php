<?php

namespace App\Jobs;

use App\Events\FeedCacheRebuildRequested;
use App\Models\Page;
use App\Models\User;
use App\Services\FeedCms\FeedCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RebuildUserFeedCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $userId, public readonly string $reason = 'scheduled')
    {
        $this->onQueue('feed-cms');
    }

    public function handle(FeedCacheService $feedCache): void
    {
        $user = User::query()->find($this->userId);

        if (! $user) {
            $this->fail("User [{$this->userId}] not found for feed cache rebuild.");
            return;
        }

        event(new FeedCacheRebuildRequested($user->id, $this->reason));

        $items = Page::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn (Page $page): array => [
                'feedable' => $page,
                'content_type' => $page->is_system_page ? 'handbook_article' : 'network_post',
                'base_score' => $this->baseScore($page),
                'source_reason' => $page->is_system_page ? 'admin_highlight' : 'fresh_content',
            ]);

        $feedCache->rebuild($user, $items, 10080);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('User feed cache rebuild failed.', [
            'user_id' => $this->userId,
            'reason' => $this->reason,
            'message' => $exception?->getMessage(),
        ]);
    }

    private function baseScore(Page $page): int
    {
        $recencyScore = $page->published_at?->greaterThan(now()->subDays(7)) ? 30 : 10;

        return $recencyScore + min((int) floor($page->view_count / 10), 50);
    }
}
