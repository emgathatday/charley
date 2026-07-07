<?php

namespace App\Jobs;

use App\Events\LibraryWatermarkPreparationRequested;
use App\Models\LibraryItem;
use App\Services\Library\LibraryAccessService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareLibraryWatermarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public LibraryItem $libraryItem, public ?string $partnerTier = null)
    {
        $this->onQueue('library-processing');
    }

    public function handle(LibraryAccessService $access): void
    {
        $this->libraryItem->loadMissing('fileMedia');

        if (! $this->libraryItem->fileMedia || ! $access->requiresWatermark($this->libraryItem, $this->partnerTier)) {
            return;
        }

        event(new LibraryWatermarkPreparationRequested($this->libraryItem));

        ApplyMediaWatermarkJob::dispatch(
            $this->libraryItem->fileMedia,
            'library/watermarked/'.$this->libraryItem->fileMedia->path,
        );
    }
}
