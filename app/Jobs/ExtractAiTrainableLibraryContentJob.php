<?php

namespace App\Jobs;

use App\Events\LibraryAiTrainableContentReady;
use App\Models\LibraryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtractAiTrainableLibraryContentJob implements ShouldQueue
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

        if (! $this->isApprovedTrainableContent()) {
            return;
        }

        if (blank($this->libraryItem->content) && filled($this->libraryItem->fileMedia?->extracted_text)) {
            $this->libraryItem->forceFill([
                'content' => $this->libraryItem->fileMedia->extracted_text,
            ])->save();
        }

        event(new LibraryAiTrainableContentReady($this->libraryItem->refresh()));
    }

    private function isApprovedTrainableContent(): bool
    {
        return $this->libraryItem->status === LibraryItem::STATUS_PUBLISHED
            && $this->libraryItem->approved_at !== null
            && $this->libraryItem->is_ai_trainable;
    }
}
