<?php

namespace App\Jobs;

use App\Models\SupportTicket;
use App\Notifications\SupportTicketUpdatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSupportTicketNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $ticketId,
        public readonly string $eventType,
        public readonly ?string $message = null,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $ticket = SupportTicket::query()->with(['user', 'assignee'])->find($this->ticketId);

        if (! $ticket) {
            $this->fail('Support ticket no longer exists.');

            return;
        }

        $recipient = $this->eventType === 'assigned' ? $ticket->assignee : $ticket->user;

        if (! $recipient) {
            $this->fail('Support ticket notification has no recipient.');

            return;
        }

        $recipient->notify(new SupportTicketUpdatedNotification($ticket, $this->eventType, $this->message));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Support ticket notification failed.', [
            'job' => self::class,
            'ticket_id' => $this->ticketId,
            'message' => $exception->getMessage(),
        ]);
    }
}
