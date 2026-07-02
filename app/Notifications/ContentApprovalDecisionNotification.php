<?php

namespace App\Notifications;

use App\Models\ContentApprovalQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContentApprovalDecisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly ContentApprovalQueue $approval)
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
            ->action('View content approval', $copy['url']);
    }

    public function toArray(object $notifiable): array
    {
        $copy = $this->copy();

        return [
            'title' => $copy['title'],
            'body' => $copy['body'],
            'url' => $copy['url'],
            'approval_id' => $this->approval->id,
            'status' => $this->approval->status,
        ];
    }

    private function copy(): array
    {
        return [
            'title' => 'Content approval '.$this->approval->status,
            'body' => "{$this->approval->content_title} was {$this->approval->status}.",
            'url' => url('/dashboard/admin-operations?approval_status='.$this->approval->status),
        ];
    }
}
