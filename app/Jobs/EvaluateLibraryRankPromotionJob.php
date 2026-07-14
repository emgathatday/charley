<?php

namespace App\Jobs;

use App\Events\LibraryRankPromotionEvaluated;
use App\Models\QuizAttempt;
use App\Notifications\LibraryQuizRankNotification;
use App\Services\ExpertiseRankService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EvaluateLibraryRankPromotionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $quizAttemptId)
    {
        $this->onQueue('library');
    }

    public function handle(ExpertiseRankService $ranks): void
    {
        $attempt = QuizAttempt::query()->with(['user', 'knowledgeDomain'])->find($this->quizAttemptId);

        if (! $attempt) {
            $this->fail("Quiz attempt [{$this->quizAttemptId}] was not found for rank promotion evaluation.");

            return;
        }

        if (! $attempt->submitted_at) {
            return;
        }

        $promotion = null;

        if ($attempt->is_passed) {
            $ranks->unlockAfterQuizPass($attempt);
            $promotion = $ranks->evaluatePromotion($attempt->user_id, $attempt->knowledgeDomain?->plant_type_id);
        }

        event(new LibraryRankPromotionEvaluated($attempt, $promotion));

        if ($attempt->user) {
            $attempt->user->notify(new LibraryQuizRankNotification($attempt, $promotion));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Library rank promotion evaluation failed.', [
            'quiz_attempt_id' => $this->quizAttemptId,
            'message' => $exception->getMessage(),
        ]);
    }
}