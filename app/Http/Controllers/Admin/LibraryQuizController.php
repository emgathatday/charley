<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDomain;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LibraryQuizController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'domain', 'search']);

        return view('admin.library.quizzes.index', [
            'filters' => $filters,
            'stats' => [
                'quizzes' => Quiz::query()->count(),
                'published' => Quiz::query()->published()->count(),
                'questions' => QuizQuestion::query()->count(),
                'attempts' => QuizAttempt::query()->completed()->count(),
            ],
            'quizzes' => Quiz::query()
                ->with(['knowledgeDomain'])
                ->withCount(['questions', 'attempts'])
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($filters['domain'] ?? null, fn ($query, string $domain) => $query->where('knowledge_domain_id', $domain))
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'domains' => KnowledgeDomain::query()->active()->orderBy('name')->get(),
            'statuses' => Quiz::STATUSES,
        ]);
    }

    public function show(Quiz $quiz): View
    {
        return view('admin.library.quizzes.show', [
            'quiz' => $quiz->load(['knowledgeDomain', 'questions']),
            'attempts' => QuizAttempt::query()->with(['user'])->where('quiz_id', $quiz->id)->latest()->paginate(20),
            'questionTypes' => QuizQuestion::TYPES,
            'domains' => KnowledgeDomain::query()->active()->orderBy('name')->get(),
            'statuses' => Quiz::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->quizData($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        $data['created_by'] = $request->user()?->id;

        $quiz = Quiz::query()->create($data);

        return redirect()->route('admin.dashboard.library.quizzes.show', $quiz)->with('status', 'Quiz created.');
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $data = $this->quizData($request, $quiz);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $quiz->update($data);

        return redirect()->route('admin.dashboard.library.quizzes.show', $quiz->refresh())->with('status', 'Quiz updated.');
    }

    public function archive(Quiz $quiz): RedirectResponse
    {
        $quiz->update(['status' => Quiz::STATUS_ARCHIVED]);

        return redirect()->route('admin.dashboard.library.quizzes.index')->with('status', 'Quiz archived.');
    }

    public function storeQuestion(Request $request, Quiz $quiz): RedirectResponse
    {
        $quiz->questions()->create($this->questionData($request));

        return redirect()->route('admin.dashboard.library.quizzes.show', $quiz)->with('status', 'Question added.');
    }

    public function updateQuestion(Request $request, QuizQuestion $quizQuestion): RedirectResponse
    {
        $quizQuestion->update($this->questionData($request));

        return redirect()->route('admin.dashboard.library.quizzes.show', $quizQuestion->quiz)->with('status', 'Question updated.');
    }

    public function destroyQuestion(QuizQuestion $quizQuestion): RedirectResponse
    {
        $quiz = $quizQuestion->quiz;
        $quizQuestion->delete();

        return redirect()->route('admin.dashboard.library.quizzes.show', $quiz)->with('status', 'Question removed.');
    }

    private function quizData(Request $request, ?Quiz $quiz = null): array
    {
        return $request->validate([
            'knowledge_domain_id' => ['required', 'integer', 'exists:knowledge_domains,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('quizzes', 'slug')->ignore($quiz?->id)],
            'description' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts_per_user' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(Quiz::STATUSES)],
        ]);
    }

    private function questionData(Request $request): array
    {
        $data = $request->validate([
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'string', Rule::in(QuizQuestion::TYPES)],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['nullable', 'string', 'max:1000'],
            'correct_answer' => ['required', 'array', 'min:1'],
            'correct_answer.*' => ['integer', 'min:0'],
            'points' => ['required', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['options'] = array_values(array_filter($data['options'], fn (?string $option): bool => filled($option)));
        $data['correct_answer'] = array_map('strval', array_values($data['correct_answer']));
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}