<?php

namespace App\Events\Qa;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionAnswered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $questionId,
        public readonly int $answerId,
        public readonly int $answerAuthorId,
    ) {}
}
