<?php

namespace App\Jobs;

use App\Models\MediaFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessMediaFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public MediaFile $mediaFile,
        public ?string $extractedText = null,
    ) {
        $this->onQueue('media-processing');
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $this->mediaFile->forceFill([
                'processing_status' => 'processing',
                'processing_error' => null,
            ])->save();

            $this->mediaFile->forceFill([
                'extracted_text' => $this->extractedText,
                'processing_status' => 'processed',
                'processing_error' => null,
            ])->save();
        });
    }

    public function failed(Throwable $exception): void
    {
        $this->mediaFile->forceFill([
            'processing_status' => 'failed',
            'processing_error' => $exception->getMessage(),
        ])->save();
    }
}
