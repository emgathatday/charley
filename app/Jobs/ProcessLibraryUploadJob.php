<?php

namespace App\Jobs;

use App\Events\LibraryUploadProcessingRequested;
use App\Models\LibraryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLibraryUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public LibraryItem $libraryItem)
    {
        $this->onQueue('library-processing');
    }

    public function handle(): void
    {
        $this->libraryItem->loadMissing('fileMedia');

        event(new LibraryUploadProcessingRequested($this->libraryItem));

        if ($this->libraryItem->fileMedia) {
            ProcessMediaFileJob::dispatch($this->libraryItem->fileMedia);
        }

        if ($this->libraryItem->fileMedia && $this->libraryItem->download_allowed) {
            PrepareLibraryWatermarkJob::dispatch($this->libraryItem);
        }

        if ($this->libraryItem->is_ai_trainable) {
            ExtractAiTrainableLibraryContentJob::dispatch($this->libraryItem);
        }
    }
}
