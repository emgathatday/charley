<?php

namespace App\Jobs;

use App\Models\LibraryAccessLog;
use App\Models\LibraryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshLibraryDownloadCountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public LibraryItem $libraryItem)
    {
        $this->onQueue('library-processing');
    }

    public function handle(): void
    {
        $downloads = $this->libraryItem->accessLogs()
            ->where('action', LibraryAccessLog::ACTION_DOWNLOAD)
            ->count();

        $views = $this->libraryItem->accessLogs()
            ->where('action', LibraryAccessLog::ACTION_VIEW)
            ->count();

        $this->libraryItem->forceFill([
            'download_count' => $downloads,
            'view_count' => $views,
        ])->save();
    }
}
