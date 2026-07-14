<?php

namespace App\Jobs;

use App\Models\HandbookArticle;
use App\Models\HandbookMetadata;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncHandbookArticleVectors implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly ?int $articleId = null,
        public readonly bool $retryFailed = false,
    ) {
        $this->onQueue('handbook');
    }

    public function handle(): void
    {
        if ($this->retryFailed) {
            $this->dispatchFailedMetadataRetries();

            return;
        }

        if (! $this->articleId) {
            $this->fail('A handbook article id is required for vector sync.');

            return;
        }

        $article = HandbookArticle::query()
            ->with(['metadata', 'relatedItems'])
            ->find($this->articleId);

        if (! $article) {
            $this->fail("Handbook article [{$this->articleId}] was not found for vector sync.");

            return;
        }

        if (! $this->isEligible($article)) {
            return;
        }

        $article->metadata()
            ->whereIn('vector_status', ['pending', 'failed'])
            ->update(['vector_status' => 'synced']);

        $article->touch();
    }

    public function failed(Throwable $exception): void
    {
        if (! $this->articleId) {
            return;
        }

        HandbookMetadata::query()
            ->where('article_id', $this->articleId)
            ->whereIn('vector_status', ['pending', 'synced'])
            ->update(['vector_status' => 'failed']);
    }

    private function dispatchFailedMetadataRetries(): void
    {
        HandbookMetadata::query()
            ->select('article_id')
            ->where('vector_status', 'failed')
            ->whereHas('article', fn ($query) => $query
                ->where('status', 'published')
                ->where('is_ai_trainable', true))
            ->distinct()
            ->orderBy('article_id')
            ->chunkById(50, function ($metadataRows): void {
                foreach ($metadataRows as $metadata) {
                    self::dispatch($metadata->article_id)->onQueue('handbook');
                }
            }, column: 'article_id');
    }

    private function isEligible(HandbookArticle $article): bool
    {
        return $article->status === 'published'
            && $article->is_ai_trainable
            && $article->metadata->isNotEmpty();
    }
}
