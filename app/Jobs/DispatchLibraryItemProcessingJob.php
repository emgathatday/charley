<?php

namespace App\Jobs;

use App\Models\LibraryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchLibraryItemProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly bool $retryAllApproved = false)
    {
        $this->onQueue('library');
    }

    public function handle(): void
    {
        LibraryItem::query()
            ->select(['id', 'status', 'approved_at', 'is_ai_trainable', 'content', 'file_media_id', 'updated_at'])
            ->where('status', 'published')
            ->whereNotNull('approved_at')
            ->where(function ($query): void {
                $query->whereNotNull('file_media_id')
                    ->orWhere(function ($innerQuery): void {
                        $innerQuery->where('is_ai_trainable', true)->whereNotNull('content');
                    });
            })
            ->when(! $this->retryAllApproved, fn ($query) => $query->where('updated_at', '>=', now()->subDay()))
            ->orderBy('id')
            ->chunkById(100, function ($items): void {
                foreach ($items as $item) {
                    ProcessLibraryItemIngestionJob::dispatch($item->id, $this->retryAllApproved)->onQueue('library');
                }
            });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Library item processing dispatcher failed.', [
            'message' => $exception->getMessage(),
        ]);
    }
}