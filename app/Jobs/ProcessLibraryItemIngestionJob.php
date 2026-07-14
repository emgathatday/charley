<?php

namespace App\Jobs;

use App\Events\LibraryItemIngestionQueued;
use App\Jobs\ProcessMediaFileJob;
use App\Models\LibraryItem;
use App\Notifications\LibraryItemIngestionNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessLibraryItemIngestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $libraryItemId,
        public readonly bool $force = false,
    ) {
        $this->onQueue('library');
    }

    public function handle(): void
    {
        $item = LibraryItem::query()->with(['fileMedia', 'user', 'approvedBy'])->find($this->libraryItemId);

        if (! $item) {
            $this->fail("Library item [{$this->libraryItemId}] was not found for ingestion.");

            return;
        }

        if (! $this->force && ! $this->isApprovedForProcessing($item)) {
            return;
        }

        $mediaExtractionQueued = false;
        $aiIngestionQueued = false;

        if ($item->fileMedia) {
            ProcessMediaFileJob::dispatch($item->fileMedia)->onQueue('media-processing');
            $mediaExtractionQueued = true;
        }

        if ($item->is_ai_trainable && filled($item->content)) {
            $aiIngestionQueued = true;
            Log::info('Library item AI ingestion queued.', [
                'library_item_id' => $item->id,
                'content_type' => $item->content_type,
                'approved_at' => $item->approved_at?->toDateTimeString(),
            ]);
        }

        event(new LibraryItemIngestionQueued($item, $mediaExtractionQueued, $aiIngestionQueued));

        $recipient = $item->approvedBy ?: $item->user;
        if ($recipient && ($mediaExtractionQueued || $aiIngestionQueued)) {
            $recipient->notify(new LibraryItemIngestionNotification($item, $mediaExtractionQueued, $aiIngestionQueued));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Library item ingestion job failed.', [
            'library_item_id' => $this->libraryItemId,
            'message' => $exception->getMessage(),
        ]);
    }

    private function isApprovedForProcessing(LibraryItem $item): bool
    {
        return $item->status === 'published'
            && $item->approved_at !== null
            && ($item->file_media_id !== null || ($item->is_ai_trainable && filled($item->content)));
    }
}