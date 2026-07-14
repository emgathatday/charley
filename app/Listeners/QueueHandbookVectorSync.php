<?php

namespace App\Listeners;

use App\Events\HandbookArticlePublished;
use App\Jobs\SyncHandbookArticleVectors;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueueHandbookVectorSync implements ShouldQueue
{
    public string $queue = 'handbook';

    public function handle(HandbookArticlePublished $event): void
    {
        if (! $event->article->is_ai_trainable) {
            return;
        }

        SyncHandbookArticleVectors::dispatch($event->article->id)->onQueue('handbook');
    }
}
