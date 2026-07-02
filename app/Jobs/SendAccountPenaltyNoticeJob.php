<?php

namespace App\Jobs;

use App\Models\AccountPenalty;
use App\Notifications\AccountPenaltyNoticeNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAccountPenaltyNoticeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int $penaltyId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $penalty = AccountPenalty::query()->with('user')->find($this->penaltyId);

        if (! $penalty) {
            $this->fail('Account penalty no longer exists.');

            return;
        }

        if (! $penalty->user) {
            $this->fail('Account penalty notice has no user.');

            return;
        }

        $penalty->user->notify(new AccountPenaltyNoticeNotification($penalty));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Account penalty notice failed.', [
            'job' => self::class,
            'penalty_id' => $this->penaltyId,
            'message' => $exception->getMessage(),
        ]);
    }
}
