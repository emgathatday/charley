<?php

namespace App\Jobs;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchLibraryRankPromotionSweepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly bool $includeRecentFailures = false)
    {
        $this->onQueue('library');
    }

    public function handle(): void
    {
        QuizAttempt::query()
            ->select(['id', 'is_passed', 'submitted_at', 'counted_for_rank_promotion'])
            ->whereNotNull('submitted_at')
            ->where(function ($query): void {
                $query->where(function ($passed): void {
                    $passed->where('is_passed', true)->where('counted_for_rank_promotion', false);
                });

                if ($this->includeRecentFailures) {
                    $query->orWhere(function ($failed): void {
                        $failed->where('is_passed', false)->where('submitted_at', '>=', now()->subDay());
                    });
                }
            })
            ->orderBy('id')
            ->chunkById(100, function ($attempts): void {
                foreach ($attempts as $attempt) {
                    EvaluateLibraryRankPromotionJob::dispatch($attempt->id)->onQueue('library');
                }
            });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Library rank promotion sweep failed.', [
            'message' => $exception->getMessage(),
        ]);
    }
}