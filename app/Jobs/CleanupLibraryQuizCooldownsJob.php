<?php

namespace App\Jobs;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanupLibraryQuizCooldownsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $olderThanDays = 30)
    {
        $this->onQueue('library');
    }

    public function handle(): void
    {
        QuizAttempt::query()
            ->where('is_passed', false)
            ->whereNotNull('next_attempt_allowed_at')
            ->where('next_attempt_allowed_at', '<', now()->subDays($this->olderThanDays))
            ->orderBy('id')
            ->chunkById(500, function ($attempts): void {
                foreach ($attempts as $attempt) {
                    $attempt->forceFill(['next_attempt_allowed_at' => null])->save();
                }
            });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Library quiz cooldown cleanup failed.', [
            'message' => $exception->getMessage(),
            'older_than_days' => $this->olderThanDays,
        ]);
    }
}