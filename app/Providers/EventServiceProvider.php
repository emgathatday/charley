<?php

namespace App\Providers;

use App\Events\ConnectionAccepted;
use App\Events\ConnectionBlocked;
use App\Events\ConnectionDeclined;
use App\Events\ConnectionPending;
use App\Events\ConnectionRequestApproved;
use App\Events\ConnectionRequestRejected;
use App\Events\ConnectionRequestSubmitted;
use App\Events\Qa\PointsChanged;
use App\Events\Qa\QuestionAnswered;
use App\Listeners\Qa\DispatchPointsChangedReputationRecalculation;
use App\Listeners\Qa\DispatchQuestionAnsweredReputationRecalculation;
use App\Listeners\SendConnectionWorkflowNotifications;
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
        ConnectionRequestSubmitted::class => [
            SendConnectionWorkflowNotifications::class,
        ],
        ConnectionRequestApproved::class => [
            SendConnectionWorkflowNotifications::class,
        ],
        ConnectionRequestRejected::class => [
            SendConnectionWorkflowNotifications::class,
        ],
        ConnectionPending::class => [
            SendConnectionWorkflowNotifications::class,
        ],
        ConnectionAccepted::class => [
            SendConnectionWorkflowNotifications::class,
        ],
        ConnectionDeclined::class => [
            SendConnectionWorkflowNotifications::class,
        ],
        ConnectionBlocked::class => [
            SendConnectionWorkflowNotifications::class,
        ],
    ];
}
