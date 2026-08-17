<?php

namespace Tests\Unit\Qa;

use App\Jobs\Qa\GenerateMonthlyLeaderboardSnapshot;
use App\Jobs\Qa\RecalculateUserReputation;
use App\Services\Qa\LeaderboardSnapshotService;
use App\Services\Qa\ReputationLedgerService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class QaJobsTest extends TestCase
{
    public function test_recalculate_user_reputation_job_calls_service_for_valid_user(): void
    {
        $service = Mockery::mock(ReputationLedgerService::class);
        $service->shouldReceive('recalculateUserReputation')
            ->once()
            ->with(15);

        (new RecalculateUserReputation(15))->handle($service);
    }

    public function test_recalculate_user_reputation_job_does_not_call_service_for_invalid_user(): void
    {
        $service = Mockery::mock(ReputationLedgerService::class);
        $service->shouldNotReceive('recalculateUserReputation');

        (new RecalculateUserReputation(0))->handle($service);
    }

    public function test_generate_monthly_leaderboard_snapshot_job_calls_service_for_valid_month(): void
    {
        $service = Mockery::mock(LeaderboardSnapshotService::class);
        $service->shouldReceive('createMonthlySnapshot')
            ->once()
            ->with('2026-07')
            ->andReturn(new Collection());

        (new GenerateMonthlyLeaderboardSnapshot('2026-07'))->handle($service);
    }

    public function test_generate_monthly_leaderboard_snapshot_job_does_not_call_service_for_invalid_month(): void
    {
        $service = Mockery::mock(LeaderboardSnapshotService::class);
        $service->shouldNotReceive('createMonthlySnapshot');

        (new GenerateMonthlyLeaderboardSnapshot('2026-13'))->handle($service);
    }
}
