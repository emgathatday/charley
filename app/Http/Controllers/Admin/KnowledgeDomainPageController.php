<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDomain;
use App\Models\PlantType;
use App\Models\QuizQuestion;
use App\Services\KnowledgeDomainQuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KnowledgeDomainPageController extends Controller
{
    public function __construct(private readonly KnowledgeDomainQuizService $quizService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $domains = KnowledgeDomain::query()
            ->with('plantType')
            ->withCount([
                'quizQuestions',
                'quizQuestions as active_questions_count' => fn ($query) => $query->where('status', 'active'),
                'quizQuestions as draft_questions_count' => fn ($query) => $query->where('status', 'draft'),
            ])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($filters['plant_type_id'] ?? null, fn ($query, int $plantTypeId) => $query->where('plant_type_id', $plantTypeId))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.library.knowledge-domains', [
            'domains' => $domains,
            'plantTypes' => $this->plantTypes(),
            'stats' => [
                'total' => KnowledgeDomain::query()->count(),
                'active' => KnowledgeDomain::query()->where('is_active', true)->count(),
                'questions' => QuizQuestion::query()->count(),
                'draft_questions' => QuizQuestion::query()->where('status', 'draft')->count(),
            ],
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.library.knowledge-domains.create', [
            'domain' => new KnowledgeDomain([
                'icon' => 'bi bi-diagram-3',
                'quiz_question_count' => 50,
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'plantTypes' => $this->plantTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $domain = $this->quizService->createDomain($this->validatedDomain($request));

        return redirect()
            ->route('admin.dashboard.library.knowledge-domains.edit', $domain)
            ->with('status', 'Knowledge domain created.');
    }

    public function edit(KnowledgeDomain $knowledgeDomain): View
    {
        $knowledgeDomain->load([
            'plantType',
            'quizQuestions' => fn ($query) => $query->with('choices')->orderBy('sort_order')->orderBy('id'),
        ]);

        return view('admin.library.knowledge-domains.edit', [
            'domain' => $knowledgeDomain,
            'plantTypes' => $this->plantTypes(),
        ]);
    }

    public function update(Request $request, KnowledgeDomain $knowledgeDomain): RedirectResponse
    {
        $this->quizService->updateDomain($knowledgeDomain, $this->validatedDomain($request, $knowledgeDomain));

        return redirect()
            ->route('admin.dashboard.library.knowledge-domains.edit', $knowledgeDomain)
            ->with('status', 'Knowledge domain updated.');
    }

    public function createQuestion(KnowledgeDomain $knowledgeDomain): View
    {
        return view('admin.library.knowledge-domains.questions.create', [
            'domain' => $knowledgeDomain,
            'question' => new QuizQuestion([
                'difficulty_level' => 'medium',
                'status' => 'draft',
                'sort_order' => 0,
            ]),
            'choiceRows' => $this->defaultChoiceRows(),
            'statuses' => ['active', 'draft', 'archived'],
        ]);
    }

    public function storeQuestion(Request $request, KnowledgeDomain $knowledgeDomain): RedirectResponse
    {
        $question = $this->quizService->createQuestion($knowledgeDomain, $this->validatedQuestion($request), $request->user());

        return redirect()
            ->route('admin.dashboard.library.knowledge-domains.questions.edit', [$knowledgeDomain, $question])
            ->with('status', 'Question created.');
    }

    public function editQuestion(KnowledgeDomain $knowledgeDomain, QuizQuestion $quizQuestion): View
    {
        $quizQuestion = $this->questionForDomain($knowledgeDomain, $quizQuestion)->load('choices');

        return view('admin.library.knowledge-domains.questions.edit', [
            'domain' => $knowledgeDomain,
            'question' => $quizQuestion,
            'choiceRows' => $quizQuestion->choices->map(fn ($choice) => [
                'choice_text' => $choice->choice_text,
                'explanation' => $choice->explanation,
                'sort_order' => $choice->sort_order,
                'is_correct' => $choice->is_correct,
            ])->values()->all(),
            'statuses' => ['active', 'draft', 'archived'],
        ]);
    }

    public function updateQuestion(Request $request, KnowledgeDomain $knowledgeDomain, QuizQuestion $quizQuestion): RedirectResponse
    {
        $quizQuestion = $this->questionForDomain($knowledgeDomain, $quizQuestion);
        $this->quizService->updateQuestion($knowledgeDomain, $quizQuestion, $this->validatedQuestion($request), $request->user());

        return redirect()
            ->route('admin.dashboard.library.knowledge-domains.questions.edit', [$knowledgeDomain, $quizQuestion])
            ->with('status', 'Question updated.');
    }

    public function cloneQuestion(Request $request, KnowledgeDomain $knowledgeDomain, QuizQuestion $quizQuestion): RedirectResponse
    {
        $quizQuestion = $this->questionForDomain($knowledgeDomain, $quizQuestion);
        $clone = $this->quizService->cloneQuestion($knowledgeDomain, $quizQuestion, $request->user());

        return redirect()
            ->route('admin.dashboard.library.knowledge-domains.questions.edit', [$knowledgeDomain, $clone])
            ->with('status', 'Question cloned.');
    }

    public function destroyQuestion(KnowledgeDomain $knowledgeDomain, QuizQuestion $quizQuestion): RedirectResponse
    {
        $this->quizService->deleteQuestion($knowledgeDomain, $quizQuestion);

        return redirect()
            ->route('admin.dashboard.library.knowledge-domains.edit', $knowledgeDomain)
            ->with('status', 'Question deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedDomain(Request $request, ?KnowledgeDomain $domain = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('knowledge_domains', 'name')->ignore($domain?->id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('knowledge_domains', 'slug')->ignore($domain?->id)],
            'description' => ['nullable', 'string'],
            'plant_type_id' => ['nullable', 'integer', 'exists:plant_types,id'],
            'icon' => ['nullable', 'string', 'max:255'],
            'quiz_question_count' => ['required', 'integer', 'min:1', 'max:200'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedQuestion(Request $request): array
    {
        $data = $request->validate([
            'question_text' => ['required', 'string'],
            'question_image_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'status' => ['required', Rule::in(['active', 'draft', 'archived'])],
            'explanation' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'choices' => ['required', 'array'],
            'choices.*.choice_text' => ['nullable', 'string'],
            'choices.*.explanation' => ['nullable', 'string'],
            'choices.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'correct_choice' => ['required', 'integer'],
        ]);

        $correctChoice = (string) $data['correct_choice'];
        $choices = [];

        foreach ($data['choices'] as $index => $choice) {
            if (blank($choice['choice_text'] ?? null)) {
                continue;
            }

            $choices[] = [
                'choice_text' => $choice['choice_text'],
                'is_correct' => (string) $index === $correctChoice,
                'explanation' => $choice['explanation'] ?? '',
                'sort_order' => $choice['sort_order'] ?? count($choices) + 1,
            ];
        }

        $validator = Validator::make(['choices' => $choices], [
            'choices' => ['array', 'min:2'],
        ]);

        if ($validator->fails()) {
            back()->withErrors(['choices' => 'Add at least two answer choices.'])->withInput()->throwResponse();
        }

        if (! collect($choices)->contains('is_correct', true)) {
            back()->withErrors(['correct_choice' => 'Select the correct answer from a filled choice row.'])->withInput()->throwResponse();
        }

        $data['difficulty_level'] = 'medium';
        $data['question_image_media_id'] = $data['question_image_media_id'] ?: null;
        $data['choices'] = $choices;

        return $data;
    }

    private function questionForDomain(KnowledgeDomain $domain, QuizQuestion $question): QuizQuestion
    {
        abort_unless($question->knowledge_domain_id === $domain->id, 404);

        return $question;
    }

    private function defaultChoiceRows(): array
    {
        return [
            ['choice_text' => '', 'explanation' => '', 'sort_order' => 1, 'is_correct' => true],
            ['choice_text' => '', 'explanation' => '', 'sort_order' => 2, 'is_correct' => false],
        ];
    }

    private function plantTypes()
    {
        return PlantType::query()->sorted()->get(['id', 'name']);
    }
}
