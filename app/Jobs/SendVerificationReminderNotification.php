<?php

namespace App\Jobs;

use App\Models\VerificationReminderSchedule;
use App\Notifications\VerificationReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendVerificationReminderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int $scheduleId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $schedule = VerificationReminderSchedule::query()->with('user')->find($this->scheduleId);

        if (! $schedule) {
            $this->fail('Verification reminder schedule no longer exists.');

            return;
        }

        if ($schedule->status !== 'pending' || $schedule->sent_at !== null) {
            return;
        }

        if (! $schedule->user) {
            $schedule->forceFill(['status' => 'cancelled'])->save();
            $this->fail('Verification reminder schedule has no user.');

            return;
        }

        $schedule->user->notify(new VerificationReminderNotification($schedule));

        $schedule->forceFill([
            'sent_at' => now(),
            'status' => 'sent',
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Verification reminder notification failed.', [
            'job' => self::class,
            'schedule_id' => $this->scheduleId,
            'message' => $exception->getMessage(),
        ]);
    }
}
