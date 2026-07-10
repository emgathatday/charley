<?php

namespace App\Services\Library;

use App\Models\ExpertiseLevel;
use App\Models\HandbookCategory;
use App\Models\PlantType;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserExpertiseRank;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpertiseRankingService
{
    public function assignCvReviewRank(
        User $user,
        ExpertiseLevel $level,
        User $admin,
        ?PlantType $plantType = null,
        ?HandbookCategory $handbookCategory = null,
        ?string $notes = null,
    ): ?UserExpertiseRank {
        return $this->assignRank(
            user: $user,
            level: $level,
            source: UserExpertiseRank::SOURCE_CV_REVIEW,
            plantType: $plantType,
            handbookCategory: $handbookCategory,
            assignedBy: $admin,
            quizAttempt: null,
            notes: $notes,
        );
    }

    public function promoteFromQuizAttempt(QuizAttempt $attempt): ?UserExpertiseRank
    {
        $attempt->loadMissing(['quiz.targetExpertiseLevel', 'user']);

        if (! $attempt->passed || ! $attempt->quiz?->targetExpertiseLevel) {
            return null;
        }

        return $this->assignRank(
            user: $attempt->user,
            level: $attempt->quiz->targetExpertiseLevel,
            source: UserExpertiseRank::SOURCE_QUIZ_PASS,
            plantType: $attempt->quiz->plantType,
            handbookCategory: $attempt->quiz->handbookCategory,
            assignedBy: null,
            quizAttempt: $attempt,
            notes: 'Assigned automatically after passing quiz.',
        );
    }

    public function currentRankForScope(User $user, ?int $plantTypeId = null, ?int $handbookCategoryId = null): ?UserExpertiseRank
    {
        return UserExpertiseRank::query()
            ->with('expertiseLevel')
            ->forUser($user->id)
            ->forScope($plantTypeId, $handbookCategoryId)
            ->highestCurrent()
            ->first();
    }

    public function bestCurrentRank(User $user, ?int $plantTypeId = null, ?int $handbookCategoryId = null): ?UserExpertiseRank
    {
        return UserExpertiseRank::query()
            ->with('expertiseLevel')
            ->forUser($user->id)
            ->current()
            ->when($handbookCategoryId !== null, fn ($query) => $query->where('handbook_category_id', $handbookCategoryId))
            ->when($handbookCategoryId === null && $plantTypeId !== null, fn ($query) => $query->whereNull('handbook_category_id')->where('plant_type_id', $plantTypeId))
            ->when($handbookCategoryId === null && $plantTypeId === null, fn ($query) => $query->whereNull('handbook_category_id')->whereNull('plant_type_id'))
            ->highestCurrent()
            ->first();
    }

    private function assignRank(
        User $user,
        ExpertiseLevel $level,
        string $source,
        ?PlantType $plantType,
        ?HandbookCategory $handbookCategory,
        ?User $assignedBy,
        ?QuizAttempt $quizAttempt,
        ?string $notes,
    ): ?UserExpertiseRank {
        $this->validateAssignment($source, $assignedBy, $quizAttempt);

        return DB::transaction(function () use ($user, $level, $source, $plantType, $handbookCategory, $assignedBy, $quizAttempt, $notes): ?UserExpertiseRank {
            $currentRank = $this->currentRankForScope($user, $plantType?->id, $handbookCategory?->id);
            $currentLevel = $currentRank?->expertiseLevel;

            if ($currentLevel && ! $level->isHigherThan($currentLevel)) {
                return null;
            }

            UserExpertiseRank::query()
                ->forUser($user->id)
                ->forScope($plantType?->id, $handbookCategory?->id)
                ->current()
                ->update(['is_current' => false]);

            return UserExpertiseRank::query()->create([
                'user_id' => $user->id,
                'expertise_level_id' => $level->id,
                'plant_type_id' => $plantType?->id,
                'handbook_category_id' => $handbookCategory?->id,
                'source' => $source,
                'assigned_by' => $assignedBy?->id,
                'quiz_attempt_id' => $quizAttempt?->id,
                'notes' => $notes,
                'is_current' => true,
                'assigned_at' => now(),
            ]);
        });
    }

    private function validateAssignment(string $source, ?User $assignedBy, ?QuizAttempt $quizAttempt): void
    {
        if (! in_array($source, UserExpertiseRank::SOURCES, true)) {
            throw ValidationException::withMessages(['source' => 'The expertise rank source is not supported.']);
        }

        if ($source === UserExpertiseRank::SOURCE_CV_REVIEW && ! $assignedBy) {
            throw ValidationException::withMessages(['assigned_by' => 'CV review expertise ranks require an assigning admin.']);
        }

        if ($source === UserExpertiseRank::SOURCE_QUIZ_PASS && ! $quizAttempt) {
            throw ValidationException::withMessages(['quiz_attempt_id' => 'Quiz-pass expertise ranks require a quiz attempt.']);
        }
    }
}
