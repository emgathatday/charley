<?php

namespace App\Notifications;

use App\Models\VerificationReminderSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly VerificationReminderSchedule $schedule)
    {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $copy = $this->copy();

        return (new MailMessage)
            ->subject($copy['title'])
            ->greeting('Hello '.$this->displayName($notifiable).',')
            ->line($copy['body'])
            ->action('Review verification status', $copy['url'])
            ->line('This reminder is part of the periodic professional verification flow.');
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(object $notifiable): array
    {
        $copy = $this->copy();

        return [
            'title' => $copy['title'],
            'body' => $copy['body'],
            'url' => $copy['url'],
            'reminder_type' => $this->schedule->reminder_type,
            'verification_expires_at' => $this->schedule->user?->verification_expires_at?->toDateTimeString(),
            'schedule_id' => $this->schedule->id,
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    private function copy(): array
    {
        $templates = [
            '30_days_before' => [
                'title' => 'Professional verification expires in 30 days',
                'body' => 'Your professional verification is approaching its renewal window. Submit renewal evidence to keep full access active.',
            ],
            '7_days_before' => [
                'title' => 'Professional verification expires in 7 days',
                'body' => 'Your professional verification expires soon. Please renew now to avoid access changes.',
            ],
            'expiry_day' => [
                'title' => 'Professional verification expires today',
                'body' => 'Your professional verification reaches its expiry date today. Renew your verification to keep your professional status current.',
            ],
            'expired_notice' => [
                'title' => 'Professional verification has expired',
                'body' => 'Your professional verification has expired. Submit a new verification request to restore verified professional status.',
            ],
        ];

        $copy = $templates[$this->schedule->reminder_type] ?? [
            'title' => 'Professional verification reminder',
            'body' => 'Please review your professional verification status.',
        ];

        return [
            'title' => $copy['title'],
            'body' => $copy['body'],
            'url' => url('/account/verification'),
        ];
    }

    private function displayName(object $notifiable): string
    {
        $name = trim((string) ($notifiable->first_name ?? '').' '.(string) ($notifiable->last_name ?? ''));

        return $name !== '' ? $name : (string) ($notifiable->username ?? $notifiable->email ?? 'there');
    }
}
