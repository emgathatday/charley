<?php

namespace App\Services;

use App\Jobs\EvaluateLibraryRankPromotionJob;
use App\Models\KnowledgeDomain;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptQuestion;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionChoice;
use App\Models\User;
use App\Models\UserDomainExpertise;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class KnowledgeDomainQuizService
{
    public function __construct(
        private readonly KnowledgeDomain $domains,
        private readonly QuizQuestion $questions,
        private readonly QuizQuestionChoice $choices,
        private readonly QuizAttempt $attempts,
        private readonly QuizAttemptQuestion $attemptQuestions,
        private readonly UserDomainExpertise $expertise,
    ) {
    }

    public function searchDomains(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->domains->newQuery()
            ->with(['plantType', 'createdBy'])
            ->when($filters['active'] ?? null, fn (Builder $query, bool $active) => $query->where('is_active', $active))
            ->when($filters['plant_type_id'] ?? null, fn (Builder $query, int $plantTypeId) => $query->where('plant_type_id', $plantTypeId))
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $innerQuery) use ($term): void {
                    $innerQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function createDomain(array $data, ?User $actor = null): KnowledgeDomain
    {
        return DB::transaction(function () use ($data, $actor): KnowledgeDomain {
            $payload = $this->domainPayload($data);

            if ($actor && empty($payload['created_by'])) {
                $payload['created_by'] = $actor->id;
            }

            $domain = $this->domains->newQuery()->create($payload);

            return $domain->refresh()->load(['plantType', 'createdBy']);
        });
    }

    public function updateDomain(KnowledgeDomain|int|string $domain, array $data): KnowledgeDomain
    {
        return DB::transaction(function () use ($domain, $data): KnowledgeDomain {
            $record = $this->resolveDomain($domain);
            $record->fill($this->domainPayload($data, true))->save();

            return $record->refresh()->load(['plantType', 'createdBy']);
        });
    }

    public function createQuestion(KnowledgeDomain|int|string $domain, array $data, ?User $actor = null): QuizQuestion
    {
        return DB::transaction(function () use ($domain, $data, $actor): QuizQuestion {
            $domainRecord = $this->resolveDomain($domain);
            $payload = $this->questionPayload($data, $domainRecord, $actor);
            $choices = $data['choices'] ?? [];

            $question = $this->questions->newQuery()->create($payload);
            $this->replaceChoices($question, $choices);
            $this->syncQuestionCount($domainRecord);

            return $question->refresh()->load(['knowledgeDomain', 'choices', 'questionImageMedia']);
        });
    }

    public function updateQuestion(KnowledgeDomain|int|string $domain, QuizQuestion|int $question, array $data, ?User $actor = null): QuizQuestion
    {
        return DB::transaction(function () use ($domain, $question, $data, $actor): QuizQuestion {
            $domainRecord = $this->resolveDomain($domain);
            $record = $this->resolveQuestion($domainRecord, $question);
            $record->fill($this->questionPayload($data, $domainRecord, $actor, true))->save();

            if (array_key_exists('choices', $data)) {
                $this->replaceChoices($record, $data['choices']);
            }

            $this->syncQuestionCount($domainRecord);

            return $record->refresh()->load(['knowledgeDomain', 'choices', 'questionImageMedia']);
        });
    }

    public function cloneQuestion(KnowledgeDomain|int|string $domain, QuizQuestion|int $question, ?User $actor = null): QuizQuestion
    {
        return DB::transaction(function () use ($domain, $question, $actor): QuizQuestion {
            $domainRecord = $this->resolveDomain($domain);
            $source = $this->resolveQuestion($domainRecord, $question)->load('choices');

            $clone = $this->questions->newQuery()->create($this->questionPayload([
                'question_text' => $source->question_text,
                'question_image_media_id' => $source->question_image_media_id,
                'difficulty_level' => $source->difficulty_level,
                'status' => $source->status,
                'explanation' => $source->explanation,
                'sort_order' => ((int) $source->sort_order) + 1,
                'choices' => $source->choices->map(fn (QuizQuestionChoice $choice): array => [
                    'choice_text' => $choice->choice_text,
                    'is_correct' => $choice->is_correct,
                    'explanation' => $choice->explanation,
                    'sort_order' => $choice->sort_order,
                ])->values()->all(),
            ], $domainRecord, $actor));

            $this->replaceChoices($clone, $source->choices->map(fn (QuizQuestionChoice $choice): array => [
                'choice_text' => $choice->choice_text,
                'is_correct' => $choice->is_correct,
                'explanation' => $choice->explanation,
                'sort_order' => $choice->sort_order,
            ])->values()->all());
            $this->syncQuestionCount($domainRecord);

            return $clone->refresh()->load(['knowledgeDomain', 'choices', 'questionImageMedia']);
        });
    }
    public function deleteQuestion(KnowledgeDomain|int|string $domain, QuizQuestion|int $question): void
    {
        DB::transaction(function () use ($domain, $question): void {
            $domainRecord = $this->resolveDomain($domain);
            $record = $this->resolveQuestion($domainRecord, $question);
            $record->delete();
            $this->syncQuestionCount($domainRecord);
        });
    }

    public function startAttempt(KnowledgeDomain|int|string $domain, User $user, int $questionLimit = 50, float $passThreshold = 80.00): QuizAttempt
    {
        return DB::transaction(function () use ($domain, $user, $questionLimit, $passThreshold): QuizAttempt {
            $domainRecord = $this->resolveDomain($domain);
            $this->assertCooldownElapsed($domainRecord, $user);

            $activeQuestionCount = $this->questions->newQuery()
                ->where('knowledge_domain_id', $domainRecord->id)
                ->where('status', 'active')
                ->count();
            $configuredQuestionCount = max(1, (int) ($domainRecord->quiz_question_count ?: 50));
            $effectiveQuestionLimit = min($configuredQuestionCount, $activeQuestionCount);

            if ($questionLimit !== 50) {
                $effectiveQuestionLimit = min($effectiveQuestionLimit, max(1, $questionLimit));
            }

            $questions = $this->questions->newQuery()
                ->where('knowledge_domain_id', $domainRecord->id)
                ->where('status', 'active')
                ->with('choices')
                ->inRandomOrder()
                ->limit($effectiveQuestionLimit)
                ->get();

            if ($questions->isEmpty()) {
                throw new InvalidArgumentException('Knowledge domain has no active quiz questions.');
            }

            $attempt = $this->attempts->newQuery()->create([
                'quiz_id' => $this->quizIdForDomain($domainRecord),
                'user_id' => $user->id,
                'knowledge_domain_id' => $domainRecord->id,
                'attempt_number' => $this->nextAttemptNumber($domainRecord, $user),
                'answers_submitted' => [],
                'score' => 0,
                'max_possible_score' => $questions->count(),
                'total_questions' => $questions->count(),
                'correct_count' => 0,
                'score_percentage' => 0,
                'pass_threshold' => $passThreshold,
                'is_passed' => false,
                'started_at' => now(),
                'submitted_at' => null,
                'next_attempt_allowed_at' => null,
                'counted_for_rank_promotion' => false,
            ]);

            foreach ($questions as $index => $question) {
                $this->attemptQuestions->newQuery()->create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_choice_id' => null,
                    'is_correct' => false,
                    'sort_order' => $index + 1,
                ]);
            }

            return $attempt->refresh()->load(['knowledgeDomain', 'attemptQuestions.question.choices']);
        });
    }

    public function submitAttempt(QuizAttempt|int $attempt, array $selectedChoicesByQuestionId): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $selectedChoicesByQuestionId): QuizAttempt {
            $record = $this->resolveAttempt($attempt);

            if ($record->submitted_at) {
                throw new InvalidArgumentException('Quiz attempt has already been submitted.');
            }

            $attemptQuestions = $this->attemptQuestions->newQuery()
                ->where('quiz_attempt_id', $record->id)
                ->with(['question.choices'])
                ->orderBy('sort_order')
                ->get();

            $correctCount = 0;
            $answers = [];

            foreach ($attemptQuestions as $attemptQuestion) {
                $questionId = $attemptQuestion->question_id;
                $selectedChoiceId = $selectedChoicesByQuestionId[$questionId] ?? null;
                $choice = $selectedChoiceId
                    ? $this->choices->newQuery()
                        ->where('question_id', $questionId)
                        ->find($selectedChoiceId)
                    : null;
                $isCorrect = (bool) $choice?->is_correct;

                if ($isCorrect) {
                    $correctCount++;
                }

                $attemptQuestion->forceFill([
                    'selected_choice_id' => $choice?->id,
                    'is_correct' => $isCorrect,
                ])->save();

                $answers[$questionId] = $choice?->id;
            }

            $totalQuestions = max(1, $attemptQuestions->count());
            $scorePercentage = round(($correctCount / $totalQuestions) * 100, 2);
            $isPassed = $scorePercentage >= (float) $record->pass_threshold;

            $record->forceFill([
                'answers_submitted' => $answers,
                'score' => $correctCount,
                'correct_count' => $correctCount,
                'score_percentage' => $scorePercentage,
                'is_passed' => $isPassed,
                'completed_at' => now(),
                'submitted_at' => now(),
                'next_attempt_allowed_at' => $isPassed ? null : now()->addDay(),
            ])->save();

            if ($isPassed) {
                $this->unlockDomainExpertise($record);
            }

            EvaluateLibraryRankPromotionJob::dispatch($record->id)->afterCommit()->onQueue('library');

            return $record->refresh()->load(['knowledgeDomain', 'attemptQuestions.question', 'attemptQuestions.selectedChoice']);
        });
    }

    public function resolveDomain(KnowledgeDomain|int|string $domain): KnowledgeDomain
    {
        if ($domain instanceof KnowledgeDomain) {
            return $domain;
        }

        $query = $this->domains->newQuery();
        $record = is_numeric($domain)
            ? $query->find((int) $domain)
            : $query->where('slug', $domain)->first();

        if (! $record) {
            throw (new ModelNotFoundException())->setModel(KnowledgeDomain::class, [$domain]);
        }

        return $record;
    }

    private function domainPayload(array $data, bool $partial = false): array
    {
        $allowed = [
            'name',
            'slug',
            'description',
            'status',
            'created_by',
            'plant_type_id',
            'icon',
            'total_question_count',
            'quiz_question_count',
            'is_active',
            'sort_order',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        if (! $partial && empty($payload['slug']) && ! empty($payload['name'])) {
            $payload['slug'] = Str::slug($payload['name']);
        }

        return $payload;
    }

    private function questionPayload(array $data, KnowledgeDomain $domain, ?User $actor = null, bool $partial = false): array
    {
        $allowed = [
            'question_text',
            'question_image_media_id',
            'difficulty_level',
            'status',
            'created_by',
            'updated_by',
            'question_type',
            'options',
            'correct_answer',
            'explanation',
            'sort_order',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));
        $payload['knowledge_domain_id'] = $domain->id;

        if (! $partial || ! empty($data['choices'])) {
            $payload['quiz_id'] = $this->quizIdForDomain($domain);
            $payload['question_type'] = $payload['question_type'] ?? 'single_choice';
            $payload['options'] = $payload['options'] ?? collect($data['choices'] ?? [])->pluck('choice_text')->values()->all();
            $payload['correct_answer'] = $payload['correct_answer'] ?? collect($data['choices'] ?? [])
                ->where('is_correct', true)
                ->pluck('choice_text')
                ->values()
                ->all();
        }

        if ($actor) {
            $payload['created_by'] = $payload['created_by'] ?? $actor->id;
            $payload['updated_by'] = $actor->id;
        }

        foreach (['difficulty_level' => ['easy', 'medium', 'hard'], 'status' => ['active', 'draft', 'archived']] as $field => $allowedValues) {
            if (array_key_exists($field, $payload) && ! in_array($payload[$field], $allowedValues, true)) {
                throw new InvalidArgumentException("Invalid quiz question {$field}.");
            }
        }

        return $payload;
    }

    private function replaceChoices(QuizQuestion $question, array $choices): void
    {
        if ($choices === []) {
            throw new InvalidArgumentException('Quiz question requires choices.');
        }

        if (collect($choices)->where('is_correct', true)->count() < 1) {
            throw new InvalidArgumentException('Quiz question requires at least one correct choice.');
        }

        $this->choices->newQuery()->where('question_id', $question->id)->delete();

        foreach (array_values($choices) as $index => $choice) {
            $this->choices->newQuery()->create([
                'question_id' => $question->id,
                'choice_text' => $choice['choice_text'],
                'is_correct' => (bool) ($choice['is_correct'] ?? false),
                'explanation' => $choice['explanation'] ?? '',
                'sort_order' => $choice['sort_order'] ?? $index + 1,
            ]);
        }
    }

    private function resolveQuestion(KnowledgeDomain $domain, QuizQuestion|int $question): QuizQuestion
    {
        if ($question instanceof QuizQuestion) {
            $record = $question;
        } else {
            $record = $this->questions->newQuery()
                ->where('knowledge_domain_id', $domain->id)
                ->find($question);
        }

        if (! $record || $record->knowledge_domain_id !== $domain->id) {
            throw (new ModelNotFoundException())->setModel(QuizQuestion::class, [$question]);
        }

        return $record;
    }

    private function resolveAttempt(QuizAttempt|int $attempt): QuizAttempt
    {
        if ($attempt instanceof QuizAttempt) {
            return $attempt;
        }

        $record = $this->attempts->newQuery()->find($attempt);

        if (! $record) {
            throw (new ModelNotFoundException())->setModel(QuizAttempt::class, [$attempt]);
        }

        return $record;
    }

    private function quizIdForDomain(KnowledgeDomain $domain): ?int
    {
        if (! Schema::hasTable('quizzes')) {
            return null;
        }

        $quiz = new class extends Model
        {
            protected $table = 'quizzes';

            protected $guarded = [];
        };

        return $quiz->newQuery()->firstOrCreate(
            ['slug' => $domain->slug.'-embedded-quiz'],
            [
                'knowledge_domain_id' => $domain->id,
                'title' => $domain->name.' Embedded Quiz',
                'description' => 'Embedded quiz managed inside Knowledge Domain edit flow.',
                'time_limit_minutes' => null,
                'max_attempts_per_user' => null,
                'status' => 'published',
                'created_by' => $domain->created_by,
            ]
        )->id;
    }

    private function nextAttemptNumber(KnowledgeDomain $domain, User $user): int
    {
        return ((int) $this->attempts->newQuery()
            ->where('knowledge_domain_id', $domain->id)
            ->where('user_id', $user->id)
            ->max('attempt_number')) + 1;
    }

    private function assertCooldownElapsed(KnowledgeDomain $domain, User $user): void
    {
        $lastAttempt = $this->attempts->newQuery()
            ->where('knowledge_domain_id', $domain->id)
            ->where('user_id', $user->id)
            ->whereNotNull('next_attempt_allowed_at')
            ->orderByDesc('next_attempt_allowed_at')
            ->first();

        if ($lastAttempt && $lastAttempt->next_attempt_allowed_at->isFuture()) {
            throw new InvalidArgumentException('Quiz retry cooldown is still active.');
        }
    }

    private function unlockDomainExpertise(QuizAttempt $attempt): void
    {
        $this->expertise->newQuery()->updateOrCreate(
            [
                'user_id' => $attempt->user_id,
                'knowledge_domain_id' => $attempt->knowledge_domain_id,
            ],
            [
                'self_rated_percentage' => 100,
                'is_quiz_unlocked' => true,
                'unlocked_at' => now(),
                'unlocked_via_attempt_id' => $attempt->id,
            ]
        );
    }

    private function syncQuestionCount(KnowledgeDomain $domain): void
    {
        $domain->forceFill([
            'total_question_count' => $this->questions->newQuery()
                ->where('knowledge_domain_id', $domain->id)
                ->count(),
        ])->save();
    }
}
