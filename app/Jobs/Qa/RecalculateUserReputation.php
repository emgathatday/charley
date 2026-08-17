<?php

namespace App\Jobs\Qa;

use App\Services\Qa\ReputationLedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RecalculateUserReputation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int $userId)
    {
        $this->onQueue('qa');
    }

    public function handle(ReputationLedgerService $reputationLedgerService): void
    {
        if ($this->userId <= 0) {
            $this->fail('A valid user id is required to recalculate reputation.');

            return;
        }

        $reputationLedgerService->recalculateUserReputation($this->userId);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            report($exception);
        }
    }
}
