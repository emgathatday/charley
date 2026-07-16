<?php

namespace App\Services\Qa;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AnswerModerationService
{
    public function __construct(
        private readonly ?QaModerationCheckService $moderationCheckService = null,
        private readonly ?WarningFreezeService $warningFreezeService = null,
    ) {}

    public function createAnswer(Question $question, array $attributes): Answer
    {
        return DB::transaction(function () use ($question, $attributes): Answer {
            $this->warningFreezeService()->assertUserCanSubmit((int) $attributes['user_id']);

            $answer = $question->answers()->create([
                'user_id' => $attributes['user_id'],
                'is_anonymous' => $attributes['is_anonymous'] ?? false,
                'body' => $attributes['body'],
                'is_admin_featured' => $attributes['is_admin_featured'] ?? false,
                'confidence_level' => $attributes['confidence_level'] ?? null,
                'admin_rank_order' => $attributes['admin_rank_order'] ?? null,
                'attachment_media_ids' => $attributes['attachment_media_ids'] ?? null,
            ]);

            $this->moderationCheckService()->checkAnswer($answer);

            return $answer;
        });
    }

    public function feature(Answer $answer, ?string $confidenceLevel = null, ?int $rankOrder = null): Answer
    {
        $this->assertValidConfidenceLevel($confidenceLevel);

        return DB::transaction(function () use ($answer, $confidenceLevel, $rankOrder): Answer {
            $answer->forceFill([
                'is_admin_featured' => true,
                'confidence_level' => $confidenceLevel,
                'admin_rank_order' => $rankOrder,
            ])->save();

            return $answer->refresh();
        });
    }

    public function unfeature(Answer $answer): Answer
    {
        return DB::transaction(function () use ($answer): Answer {
            $answer->forceFill([
                'is_admin_featured' => false,
                'confidence_level' => null,
                'admin_rank_order' => null,
            ])->save();

            return $answer->refresh();
        });
    }

    public function reorderFeaturedAnswers(Question $question, array $answerRankMap): void
    {
        DB::transaction(function () use ($question, $answerRankMap): void {
            foreach ($answerRankMap as $answerId => $rankOrder) {
                $updated = Answer::query()
                    ->where('question_id', $question->id)
                    ->where('id', $answerId)
                    ->update([
                        'is_admin_featured' => true,
                        'admin_rank_order' => $rankOrder,
                    ]);

                if ($updated === 0) {
                    throw new InvalidArgumentException("Answer {$answerId} does not belong to the question.");
                }
            }
        });
    }

    private function moderationCheckService(): QaModerationCheckService
    {
        return $this->moderationCheckService ?? new QaModerationCheckService;
    }

    private function warningFreezeService(): WarningFreezeService
    {
        return $this->warningFreezeService ?? new WarningFreezeService;
    }

    private function assertValidConfidenceLevel(?string $confidenceLevel): void
    {
        if ($confidenceLevel !== null && ! in_array($confidenceLevel, ['low', 'medium', 'high'], true)) {
            throw new InvalidArgumentException('confidence_level must be low, medium, high, or null.');
        }
    }
}
