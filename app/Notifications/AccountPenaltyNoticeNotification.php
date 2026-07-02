<?php

namespace App\Notifications;

use App\Models\AccountPenalty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountPenaltyNoticeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly AccountPenalty $penalty)
    {
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
            ->action('Review account status', $copy['url']);
    }

    public function toArray(object $notifiable): array
    {
        $copy = $this->copy();

        return [
            'title' => $copy['title'],
            'body' => $copy['body'],
            'url' => $copy['url'],
            'penalty_id' => $this->penalty->id,
            'action_type' => $this->penalty->action_type,
        ];
    }

    private function copy(): array
    {
        return [
            'title' => 'Account action: '.str_replace('_', ' ', $this->penalty->action_type),
            'body' => $this->penalty->reason,
            'url' => url('/account/security'),
        ];
    }
}
