<?php

namespace App\Services\Qa;

use App\Models\LeaderboardSetting;
use App\Models\MonthlyLeaderboardSnapshot;
use App\Models\PointTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaderboardSnapshotService
{
    public function createMonthlySnapshot(string $yearMonth): Collection
    {
        $month = CarbonImmutable::createFromFormat('Y-m', $yearMonth)->startOfMonth();

        return DB::transaction(function () use ($month, $yearMonth): Collection {
            $setting = $this->settingFor($month);

            MonthlyLeaderboardSnapshot::query()
                ->where('year_month', $yearMonth)
                ->delete();

            $rankedUsers = PointTransaction::query()
                ->select('user_id')
                ->selectRaw('SUM(points) as total_points_in_month')
                ->whereBetween('created_at', [$month, $month->endOfMonth()])
                ->groupBy('user_id')
                ->havingRaw('SUM(points) >= ?', [$setting->min_points_threshold])
                ->orderByDesc('total_points_in_month')
                ->limit($setting->top_n)
                ->get();

            return $rankedUsers
                ->values()
                ->map(function ($row, int $index) use ($yearMonth): MonthlyLeaderboardSnapshot {
                    return MonthlyLeaderboardSnapshot::query()->create([
                        'user_id' => $row->user_id,
                        'year_month' => $yearMonth,
                        'total_points_in_month' => (int) $row->total_points_in_month,
                        'rank_position' => $index + 1,
                        'created_at' => now(),
                    ]);
                });
        });
    }

    private function settingFor(CarbonImmutable $month): LeaderboardSetting
    {
        $setting = LeaderboardSetting::query()
            ->where('effective_from', '<=', $month->toDateString())
            ->orderByDesc('effective_from')
            ->first();

        if (! $setting) {
            throw new RuntimeException('No leaderboard setting is effective for the requested month.');
        }

        return $setting;
    }
}
