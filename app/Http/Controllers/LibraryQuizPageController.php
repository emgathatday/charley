<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeDomain;
use App\Models\LibraryItem;
use App\Models\LibraryItemHotspot;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserDomainPoint;
use App\Services\Library\QuizScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

class LibraryQuizPageController extends Controller
{
    public function __construct(
        private readonly QuizScoringService $quizScoringService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['domain']);
        $userPoints = $this->userDomainPoints($request);

        return view('library.quizzes.index', [
            'filters' => $filters,
            'domains' => KnowledgeDomain::query()->active()->orderBy('name')->get(),
            'userPoints' => $userPoints,
            'quizzes' => Quiz::query()
                ->with(['knowledgeDomain'])
                ->withCount('questions')
                ->published()
                ->when($filters['domain'] ?? null, fn ($query, string $domain) => $query->where('knowledge_domain_id', $domain))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function show(Request $request, Quiz $quiz): View
    {
        abort_unless($quiz->isPublished(), 404);

        return view('library.quizzes.show', [
            'quiz' => $quiz->load(['knowledgeDomain.rankTiers', 'questions']),
            'attemptsUsed' => $request->user()
                ? QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $request->user()->id)->count()
                : 0,
            'domainPoint' => $request->user()
                ? UserDomainPoint::query()->with('currentRankTier')->where('user_id', $request->user()->id)->where('knowledge_domain_id', $quiz->knowledge_domain_id)->first()
                : null,
        ]);
    }

    public function submit(Request $request, Quiz $quiz): RedirectResponse
    {
        abort_unless($quiz->isPublished(), 404);

        $data = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        try {
            $attempt = $this->quizScoringService->submitQuiz($quiz, $request->user(), $data['answers']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['answers' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('library.quizzes.result', $attempt);
    }

    public function result(Request $request, QuizAttempt $quizAttempt): View
    {
        abort_unless($quizAttempt->user_id === $request->user()?->id || $request->user()?->role === 'admin', 403);

        return view('library.quizzes.result', [
            'attempt' => $quizAttempt->load(['quiz.knowledgeDomain', 'bestScore']),
            'domainPoint' => UserDomainPoint::query()
                ->with(['knowledgeDomain', 'currentRankTier'])
                ->where('user_id', $quizAttempt->user_id)
                ->where('knowledge_domain_id', $quizAttempt->quiz?->knowledge_domain_id)
                ->first(),
        ]);
    }

    public function ranks(Request $request): View
    {
        return view('library.quizzes.ranks', [
            'domainPoints' => UserDomainPoint::query()
                ->with(['knowledgeDomain.rankTiers', 'currentRankTier'])
                ->where('user_id', $request->user()->id)
                ->orderByDesc('total_points')
                ->get(),
            'domains' => KnowledgeDomain::query()->with('rankTiers')->active()->orderBy('name')->get(),
        ]);
    }

    public function hotspots(LibraryItem $libraryItem): View
    {
        return view('library.hotspots.index', [
            'item' => $libraryItem,
            'hotspots' => LibraryItemHotspot::query()
                ->with(['knowledgeDomain'])
                ->where('library_item_id', $libraryItem->id)
                ->ordered()
                ->get(),
            'relatedQuizzes' => Quiz::query()
                ->with('knowledgeDomain')
                ->published()
                ->whereIn('knowledge_domain_id', LibraryItemHotspot::query()->where('library_item_id', $libraryItem->id)->pluck('knowledge_domain_id'))
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }

    private function userDomainPoints(Request $request): Collection
    {
        if (! $request->user()) {
            return collect();
        }

        return UserDomainPoint::query()
            ->with(['currentRankTier'])
            ->where('user_id', $request->user()->id)
            ->get()
            ->keyBy('knowledge_domain_id');
    }
}