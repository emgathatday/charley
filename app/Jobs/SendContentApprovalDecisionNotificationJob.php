<?php

namespace App\Jobs;

use App\Models\ContentApprovalQueue;
use App\Notifications\ContentApprovalDecisionNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendContentApprovalDecisionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int $approvalId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $approval = ContentApprovalQueue::query()->with('submitter')->find($this->approvalId);

        if (! $approval) {
            $this->fail('Content approval item no longer exists.');

            return;
        }

        if (! $approval->submitter) {
            $this->fail('Content approval notification has no submitter.');

            return;
        }

        $approval->submitter->notify(new ContentApprovalDecisionNotification($approval));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Content approval decision notification failed.', [
            'job' => self::class,
            'approval_id' => $this->approvalId,
            'message' => $exception->getMessage(),
        ]);
    }
}
