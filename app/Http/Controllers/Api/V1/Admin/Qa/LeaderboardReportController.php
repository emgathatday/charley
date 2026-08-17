<?php

namespace App\Http\Controllers\Api\V1\Admin\Qa;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Qa\LeaderboardReportResource;
use App\Models\MonthlyLeaderboardSnapshot;
use App\Services\Qa\LeaderboardSnapshotService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaderboardReportController extends Controller
{
    public function __construct(private readonly LeaderboardSnapshotService $leaderboardSnapshotService) {}

    public function monthly(?string $yearMonth = null): AnonymousResourceCollection
    {
        $yearMonth ??= now()->format('Y-m');

        $snapshots = MonthlyLeaderboardSnapshot::query()
            ->with('user')
            ->where('year_month', $yearMonth)
            ->orderBy('rank_position')
            ->paginate((int) request('per_page', 20));

        return LeaderboardReportResource::collection($snapshots);
    }

    public function snapshot(string $yearMonth): AnonymousResourceCollection
    {
        return LeaderboardReportResource::collection($this->leaderboardSnapshotService->createMonthlySnapshot($yearMonth)->load('user'));
    }
}
