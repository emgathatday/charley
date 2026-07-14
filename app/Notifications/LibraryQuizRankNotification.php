<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use App\Models\UserExpertiseRank;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LibraryQuizRankNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly QuizAttempt $quizAttempt,
        public readonly ?UserExpertiseRank $promotion = null,
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
            ->action('View knowledge domains', $copy['url']);
    }

    public function toArray(object $notifiable): array
    {
        $copy = $this->copy();

        return [
            'title' => $copy['title'],
            'body' => $copy['body'],
            'url' => $copy['url'],
            'quiz_attempt_id' => $this->quizAttempt->id,
            'knowledge_domain_id' => $this->quizAttempt->knowledge_domain_id,
            'score_percentage' => $this->quizAttempt->score_percentage,
            'is_passed' => $this->quizAttempt->is_passed,
            'promotion_id' => $this->promotion?->id,
            'rank_tier_id' => $this->promotion?->rank_tier_id,
        ];
    }

    private function copy(): array
    {
        $domainName = $this->quizAttempt->knowledgeDomain?->name ?? 'your knowledge domain';

        if ($this->promotion) {
            $rankName = $this->promotion->rankTier?->name ?? 'next rank';

            return [
                'title' => 'Library rank promotion earned',
                'body' => "Your {$domainName} quiz pass helped promote you to {$rankName}.",
                'url' => url('/dashboard/library/knowledge-domains'),
            ];
        }

        return [
            'title' => $this->quizAttempt->is_passed ? 'Library quiz passed' : 'Library quiz cooldown active',
            'body' => $this->quizAttempt->is_passed
                ? "You passed the {$domainName} quiz with {$this->quizAttempt->score_percentage}%."
                : "Your {$domainName} quiz is available again after the cooldown window.",
            'url' => url('/dashboard/library/knowledge-domains'),
        ];
    }
}