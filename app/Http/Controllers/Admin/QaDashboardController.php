<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Qa\StoreQuestionRequest;
use App\Services\Admin\QaDashboardViewDataService;
use App\Services\Admin\QaQuestionStoreService;
use Illuminate\Http\Request;

class QaDashboardController extends Controller
{
    public function __construct(private readonly QaDashboardViewDataService $viewData)
    {
    }

    public function index(Request $request)
    {
        return view('admin.qa.index', $this->dashboardData($request));
    }

    public function questions(Request $request)
    {
        return view('admin.qa.index', $this->dashboardData($request));
    }

    public function createQuestion(Request $request)
    {
        return view('admin.qa.question-create', $this->viewData->createQuestionData($request->query()));
    }

    public function storeQuestion(StoreQuestionRequest $request, QaQuestionStoreService $storeService)
    {
        $questionId = $storeService->store($request->validated(), (int) $request->user()->id);

        return redirect()
            ->route('admin.dashboard.qa.questions.show', $questionId)
            ->with('success', 'Question created.');
    }

    public function questionDetail(Request $request, int $question)
    {
        return view('admin.qa.question-detail', [
            ...$this->dashboardData($request),
            'question' => $this->viewData->questionDetailData($question),
            'questionAnswers' => $this->viewData->questionAnswersData($question),
            'warningHistory' => $this->viewData->warningHistoryData($question),
        ]);
    }

    public function answers(Request $request)
    {
        return view('admin.qa.answers', $this->dashboardData($request));
    }

    public function weeklyThemes(Request $request)
    {
        return view('admin.qa.weekly-themes', $this->dashboardData($request));
    }

    public function reputation(Request $request)
    {
        return view('admin.qa.reputation', $this->dashboardData($request));
    }

    public function leaderboard(Request $request)
    {
        return view('admin.qa.leaderboard', $this->dashboardData($request));
    }

    public function leaderboardReport(Request $request)
    {
        return view('admin.qa.leaderboard-report', $this->dashboardData($request));
    }

    public function flagged(Request $request)
    {
        $filters = $request->query();
        $filters['status'] = $request->query('flag_status') ?: 'flagged';

        return view('admin.qa.flagged', $this->viewData->data($filters));
    }

    public function storeQuestionDetailStatus(Request $request, int $question)
    {
        $status = (string) $request->input('status');
        abort_unless(in_array($status, ['active', 'draft', 'unactive', 'pending', 'published', 'hidden', 'flagged'], true), 404);
        $this->viewData->updateQuestionStatus($question, $status);

        return back()->with('success', 'Question status updated.');
    }

    public function updateQuestionStatus(int $question, string $status)
    {
        abort_unless(in_array($status, ['published', 'hidden', 'flagged'], true), 404);
        $this->viewData->updateQuestionStatus($question, $status);

        return back()->with('success', 'Question status updated.');
    }

    public function featureAnswer(Request $request, int $answer)
    {
        $this->viewData->featureAnswer($answer, [
            'confidence_level' => $request->input('confidence_level'),
            'admin_rank_order' => $request->integer('admin_rank_order') ?: null,
        ]);

        return back()->with('success', 'Answer featured.');
    }

    public function unfeatureAnswer(int $answer)
    {
        $this->viewData->unfeatureAnswer($answer);

        return back()->with('success', 'Answer unfeatured.');
    }

    public function storeWeeklyTheme(Request $request)
    {
        $this->viewData->storeWeeklyTheme([
            'title' => (string) $request->string('title'),
            'description' => (string) $request->string('description'),
            'week_start_date' => $request->date('week_start_date'),
            'week_end_date' => $request->date('week_end_date'),
            'created_by_admin_id' => $request->user()->id,
            'status' => $request->input('status', 'active'),
        ]);

        return back()->with('success', 'Weekly theme saved.');
    }

    public function updateWeeklyThemeStatus(int $weeklyTheme, string $status)
    {
        abort_unless(in_array($status, ['active', 'archived'], true), 404);
        $this->viewData->updateWeeklyThemeStatus($weeklyTheme, $status);

        return back()->with('success', 'Weekly theme status updated.');
    }

    public function assignWeeklyThemeQuestion(Request $request, int $weeklyTheme)
    {
        $this->viewData->assignWeeklyThemeQuestion($weeklyTheme, $request->integer('question_id'));

        return back()->with('success', 'Question assigned to weekly theme.');
    }

    public function removeWeeklyThemeQuestion(int $weeklyTheme, int $question)
    {
        $this->viewData->removeWeeklyThemeQuestion($weeklyTheme, $question);

        return back()->with('success', 'Question removed from weekly theme.');
    }

    public function storeReputationAdjustment(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'direction' => ['required', 'in:positive,negative'],
            'points' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $this->viewData->storeReputationAdjustment($validated, $request->user()->id);

        return back()->with('success', 'Reputation adjustment recorded.');
    }

    public function storeLeaderboardSettings(Request $request)
    {
        $this->viewData->storeLeaderboardSettings([
            'min_points_threshold' => $request->integer('min_points_threshold'),
            'top_n' => $request->integer('top_n'),
            'effective_from' => $request->date('effective_from'),
        ]);

        return back()->with('success', 'Leaderboard settings saved.');
    }

    public function snapshotLeaderboard(Request $request)
    {
        $this->viewData->snapshotLeaderboard((string) $request->input('year_month', now()->format('Y-m')));

        return back()->with('success', 'Leaderboard snapshot refreshed.');
    }

    private function dashboardData(Request $request): array
    {
        return $this->viewData->data($request->query());
    }
}
