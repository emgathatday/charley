<?php

namespace App\Jobs;

use App\Events\LibraryAccessAnalyticsAggregated;
use App\Models\LibraryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AggregateLibraryAccessAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly ?int $libraryItemId = null)
    {
        $this->onQueue('library');
    }

    public function handle(): void
    {
        $itemsUpdated = 0;
        $viewsCounted = 0;
        $downloadsCounted = 0;

        DB::table('library_access_logs')
            ->select('library_item_id')
            ->selectRaw("sum(case when action = 'view' then 1 else 0 end) as view_total")
            ->selectRaw("sum(case when action = 'download' then 1 else 0 end) as download_total")
            ->when($this->libraryItemId, fn ($query) => $query->where('library_item_id', $this->libraryItemId))
            ->groupBy('library_item_id')
            ->orderBy('library_item_id')
            ->chunk(100, function ($rows) use (&$itemsUpdated, &$viewsCounted, &$downloadsCounted): void {
                foreach ($rows as $row) {
                    LibraryItem::query()
                        ->whereKey($row->library_item_id)
                        ->update([
                            'view_count' => (int) $row->view_total,
                            'download_count' => (int) $row->download_total,
                            'updated_at' => now(),
                        ]);

                    $itemsUpdated++;
                    $viewsCounted += (int) $row->view_total;
                    $downloadsCounted += (int) $row->download_total;
                }
            });

        event(new LibraryAccessAnalyticsAggregated($itemsUpdated, $viewsCounted, $downloadsCounted));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Library access analytics aggregation failed.', [
            'library_item_id' => $this->libraryItemId,
            'message' => $exception->getMessage(),
        ]);
    }
}