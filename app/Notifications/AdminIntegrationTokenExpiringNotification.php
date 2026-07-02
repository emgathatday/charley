<?php

namespace App\Notifications;

use App\Models\AdminIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminIntegrationTokenExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly AdminIntegration $integration)
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
            ->action('Review integrations', $copy['url']);
    }

    public function toArray(object $notifiable): array
    {
        $copy = $this->copy();

        return [
            'title' => $copy['title'],
            'body' => $copy['body'],
            'url' => $copy['url'],
            'integration_id' => $this->integration->id,
            'provider' => $this->integration->provider,
            'token_expires_at' => $this->integration->token_expires_at?->toDateTimeString(),
        ];
    }

    private function copy(): array
    {
        return [
            'title' => 'Admin integration token expiring',
            'body' => "{$this->integration->provider} token expires at {$this->integration->token_expires_at}.",
            'url' => url('/dashboard/admin-operations'),
        ];
    }
}
