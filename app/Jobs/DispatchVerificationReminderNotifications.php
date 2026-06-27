<?php

namespace App\Jobs;

use App\Models\VerificationReminderSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchVerificationReminderNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        VerificationReminderSchedule::query()
            ->with('user')
            ->where('status', 'pending')
            ->whereNull('sent_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->chunkById(100, function ($schedules): void {
                foreach ($schedules as $schedule) {
                    if (! $schedule->user) {
                        $schedule->forceFill(['status' => 'cancelled'])->save();

                        continue;
                    }

                    SendVerificationReminderNotification::dispatch($schedule->id)
                        ->onQueue('notifications');
                }
            });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Verification reminder dispatch scan failed.', [
            'job' => self::class,
            'message' => $exception->getMessage(),
        ]);
    }
}
