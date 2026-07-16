<?php

namespace App\Providers;

use App\Events\Qa\PointsChanged;
use App\Events\Qa\QuestionAnswered;
use App\Listeners\Qa\DispatchPointsChangedReputationRecalculation;
use App\Listeners\Qa\DispatchQuestionAnsweredReputationRecalculation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        QuestionAnswered::class => [
            DispatchQuestionAnsweredReputationRecalculation::class,
        ],
        PointsChanged::class => [
            DispatchPointsChangedReputationRecalculation::class,
        ],
    ];
}
