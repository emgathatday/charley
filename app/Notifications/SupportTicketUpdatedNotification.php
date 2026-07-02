<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly string $eventType,
        public readonly ?string $message = null,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $copy = $this->copy();

        return (new MailMessage)
            ->subject($copy['title'])
            ->line($copy['body'])
            ->action('View support ticket', $copy['url']);
    }

    public function toArray(object $notifiable): array
    {
        $copy = $this->copy();

        return [
            'title' => $copy['title'],
            'body' => $copy['body'],
            'url' => $copy['url'],
            'ticket_id' => $this->ticket->id,
            'event_type' => $this->eventType,
        ];
    }

    private function copy(): array
    {
        $title = match ($this->eventType) {
            'assigned' => 'Support ticket assigned',
            'reply' => 'Support ticket reply added',
            'resolved' => 'Support ticket resolved',
            default => 'Support ticket updated',
        };

        return [
            'title' => $title,
            'body' => $this->message ?? "Ticket #{$this->ticket->id}: {$this->ticket->subject}",
            'url' => url('/dashboard/admin-operations?ticket_status='.$this->ticket->status),
        ];
    }
}
