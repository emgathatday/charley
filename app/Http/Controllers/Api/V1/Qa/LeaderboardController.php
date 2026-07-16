<?php

namespace App\Http\Controllers\Api\V1\Qa;

use App\Http\Controllers\Controller;
use App\Http\Resources\Qa\LeaderboardSnapshotResource;
use App\Models\MonthlyLeaderboardSnapshot;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaderboardController extends Controller
{
    public function monthly(?string $yearMonth = null): AnonymousResourceCollection
    {
        $yearMonth ??= now()->format('Y-m');

        $snapshots = MonthlyLeaderboardSnapshot::query()
            ->with('user')
            ->where('year_month', $yearMonth)
            ->orderBy('rank_position')
            ->paginate((int) request('per_page', 20));

        return LeaderboardSnapshotResource::collection($snapshots);
    }
}
