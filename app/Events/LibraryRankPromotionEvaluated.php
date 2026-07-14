<?php

namespace App\Events;

use App\Models\QuizAttempt;
use App\Models\UserExpertiseRank;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LibraryRankPromotionEvaluated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly QuizAttempt $quizAttempt,
        public readonly ?UserExpertiseRank $promotion,
    ) {}
}