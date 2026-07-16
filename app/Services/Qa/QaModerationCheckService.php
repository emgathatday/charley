<?php

namespace App\Services\Qa;

use App\Models\Answer;
use App\Models\QaModerationWarning;
use App\Models\Question;
use App\Services\Qa\Moderation\AiModerationProvider;
use App\Services\Qa\Moderation\QaModerationProvider;
use App\Services\Qa\Moderation\SystemRuleModerationProvider;
use Illuminate\Database\Eloquent\Model;

class QaModerationCheckService
{
    public function __construct(
        private readonly ?QaModerationProvider $systemRuleProvider = null,
        private readonly ?QaModerationProvider $aiProvider = null,
    ) {}

    public function checkQuestion(Question $question): ?QaModerationWarning
    {
        return $this->check('question', $question, [
            'title' => $question->title,
            'body' => $question->body,
            'attachments' => $this->attachmentsFromIds($question->attachment_media_ids),
        ]);
    }

    public function checkAnswer(Answer $answer): ?QaModerationWarning
    {
        return $this->check('answer', $answer, [
            'title' => $answer->question?->title,
            'body' => $answer->body,
            'attachments' => $this->attachmentsFromIds($answer->attachment_media_ids),
        ]);
    }

    private function check(string $targetType, Model $warnable, array $payload): ?QaModerationWarning
    {
        $payload = [
            ...$payload,
            'target_type' => $targetType,
            'user_id' => $warnable->user_id,
            'warnable_id' => $warnable->id,
        ];

        $systemRisk = $this->systemRuleProvider()->check($payload);
        if ($systemRisk !== null) {
            return $this->createWarning($targetType, $warnable, $systemRisk);
        }

        if (! $this->aiModerationEnabled()) {
            return null;
        }

        $aiRisk = $this->aiProvider()->check($payload);

        return $aiRisk !== null ? $this->createWarning($targetType, $warnable, [
            'source' => 'ai',
            ...$aiRisk,
        ]) : null;
    }

    private function createWarning(string $targetType, Model $warnable, array $risk): QaModerationWarning
    {
        return QaModerationWarning::query()->create([
            'user_id' => $warnable->user_id,
            'warnable_type' => $targetType,
            'warnable_id' => $warnable->id,
            'source' => $risk['source'],
            'severity' => $risk['severity'] ?? 'medium',
            'reason' => $risk['reason'],
            'evidence' => $risk['evidence'] ?? null,
            'status' => 'pending_review',
        ]);
    }

    private function systemRuleProvider(): QaModerationProvider
    {
        return $this->systemRuleProvider ?? new SystemRuleModerationProvider;
    }

    private function aiProvider(): QaModerationProvider
    {
        return $this->aiProvider ?? new AiModerationProvider;
    }

    private function aiModerationEnabled(): bool
    {
        return (bool) config('qa.ai_moderation_enabled', false);
    }

    private function attachmentsFromIds(?array $attachmentMediaIds): array
    {
        return collect($attachmentMediaIds ?? [])
            ->filter()
            ->map(fn ($id): array => ['id' => $id])
            ->values()
            ->all();
    }
}
