<?php

namespace App\Jobs;

use App\Events\LibraryAccessLogged;
use App\Models\LibraryAccessLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateLibraryAccessLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public LibraryAccessLog $accessLog)
    {
        $this->onQueue('library-processing');
    }

    public function handle(): void
    {
        $this->accessLog->loadMissing('item');

        if (! $this->accessLog->item) {
            return;
        }

        RefreshLibraryDownloadCountJob::dispatch($this->accessLog->item);

        event(new LibraryAccessLogged($this->accessLog));
    }
}
