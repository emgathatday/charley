<?php

namespace App\Notifications;

use App\Models\LibraryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LibraryItemIngestionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly LibraryItem $libraryItem,
        public readonly bool $mediaExtractionQueued,
        public readonly bool $aiIngestionQueued,
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
            ->action('Review library item', $copy['url']);
    }

    public function toArray(object $notifiable): array
    {
        $copy = $this->copy();

        return [
            'title' => $copy['title'],
            'body' => $copy['body'],
            'url' => $copy['url'],
            'library_item_id' => $this->libraryItem->id,
            'media_extraction_queued' => $this->mediaExtractionQueued,
            'ai_ingestion_queued' => $this->aiIngestionQueued,
        ];
    }

    private function copy(): array
    {
        $queued = collect([
            $this->mediaExtractionQueued ? 'media extraction' : null,
            $this->aiIngestionQueued ? 'AI ingestion' : null,
        ])->filter()->join(' and ');

        return [
            'title' => 'Library item queued for processing',
            'body' => "{$this->libraryItem->title} has been handed off for {$queued}.",
            'url' => url('/dashboard/library/items/'.$this->libraryItem->id),
        ];
    }
}