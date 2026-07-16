<?php

namespace App\Jobs\Qa;

use App\Services\Qa\LeaderboardSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateMonthlyLeaderboardSnapshot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly string $yearMonth)
    {
        $this->onQueue('qa');
    }

    public function handle(LeaderboardSnapshotService $leaderboardSnapshotService): void
    {
        if (! $this->isValidYearMonth($this->yearMonth)) {
            $this->fail('A valid year_month value in YYYY-MM format is required.');

            return;
        }

        $leaderboardSnapshotService->createMonthlySnapshot($this->yearMonth);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            report($exception);
        }
    }

    private function isValidYearMonth(string $yearMonth): bool
    {
        try {
            $month = CarbonImmutable::createFromFormat('Y-m', $yearMonth);
        } catch (Throwable) {
            return false;
        }

        return $month->format('Y-m') === $yearMonth;
    }
}
