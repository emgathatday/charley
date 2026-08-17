<?php

namespace App\Services\Qa;

use App\Models\Question;
use App\Models\WeeklyTheme;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuestionWorkflowService
{
    public function __construct(
        private readonly ?QaModerationCheckService $moderationCheckService = null,
        private readonly ?WarningFreezeService $warningFreezeService = null,
    ) {}

    public function createQuestion(array $attributes): Question
    {
        return DB::transaction(function () use ($attributes): Question {
            $this->assertValidAdminProxyPosting($attributes);
            $this->warningFreezeService()->assertUserCanSubmit((int) $attributes['user_id']);

            $question = Question::query()->create([
                'user_id' => $attributes['user_id'],
                'posted_by_admin_id' => $attributes['posted_by_admin_id'] ?? null,
                'on_behalf_of_partner_id' => $attributes['on_behalf_of_partner_id'] ?? null,
                'weekly_theme_id' => $attributes['weekly_theme_id'] ?? null,
                'plant_type_id' => $attributes['plant_type_id'] ?? null,
                'title' => $attributes['title'],
                'body' => $attributes['body'],
                'is_anonymous' => $attributes['is_anonymous'] ?? false,
                'status' => $attributes['status'] ?? 'pending',
                'attachment_media_ids' => $attributes['attachment_media_ids'] ?? null,
            ]);

            $this->moderationCheckService()->checkQuestion($question);

            return $question;
        });
    }

    public function publish(Question $question): Question
    {
        return $this->setStatus($question, 'published');
    }

    public function hide(Question $question): Question
    {
        return $this->setStatus($question, 'hidden');
    }

    public function flag(Question $question): Question
    {
        return $this->setStatus($question, 'flagged');
    }

    public function assignWeeklyTheme(Question $question, WeeklyTheme $weeklyTheme): Question
    {
        return DB::transaction(function () use ($question, $weeklyTheme): Question {
            $question->forceFill(['weekly_theme_id' => $weeklyTheme->id])->save();

            return $question->refresh();
        });
    }

    public function linkKnowledgeDomains(Question $question, array $knowledgeDomainIds): Question
    {
        return DB::transaction(function () use ($question, $knowledgeDomainIds): Question {
            $question->knowledgeDomains()->sync(array_values(array_unique($knowledgeDomainIds)));

            return $question->refresh();
        });
    }

    private function setStatus(Question $question, string $status): Question
    {
        return DB::transaction(function () use ($question, $status): Question {
            $question->forceFill(['status' => $status])->save();

            return $question->refresh();
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

    private function assertValidAdminProxyPosting(array $attributes): void
    {
        if (! empty($attributes['on_behalf_of_partner_id']) && empty($attributes['posted_by_admin_id'])) {
            throw new InvalidArgumentException('posted_by_admin_id is required when posting on behalf of a partner.');
        }
    }
}
