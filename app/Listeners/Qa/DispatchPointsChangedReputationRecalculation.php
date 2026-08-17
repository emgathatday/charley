<?php

namespace App\Listeners\Qa;

use App\Events\Qa\PointsChanged;
use App\Jobs\Qa\RecalculateUserReputation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DispatchPointsChangedReputationRecalculation implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'qa';

    public function handle(PointsChanged $event): void
    {
        RecalculateUserReputation::dispatch($event->userId)->onQueue('qa');
    }
}
