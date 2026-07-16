<?php

namespace App\Listeners\Qa;

use App\Events\Qa\QuestionAnswered;
use App\Jobs\Qa\RecalculateUserReputation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DispatchQuestionAnsweredReputationRecalculation implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'qa';

    public function handle(QuestionAnswered $event): void
    {
        RecalculateUserReputation::dispatch($event->answerAuthorId)->onQueue('qa');
    }
}
