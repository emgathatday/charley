<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QaDashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.qa.index', $this->viewData($request));
    }

    public function questions(Request $request)
    {
        return view('admin.qa.index', $this->viewData($request));
    }

    public function questionDetail(Request $request, int $question)
    {
        return view('admin.qa.question-detail', [
            ...$this->viewData($request),
            'question' => $this->questionDetailData($question),
            'questionAnswers' => $this->questionAnswersData($question),
            'warningHistory' => $this->demoWarningHistory(),
        ]);
    }

    public function answers(Request $request)
    {
        return view('admin.qa.answers', $this->viewData($request));
    }

    public function weeklyThemes(Request $request)
    {
        return view('admin.qa.weekly-themes', $this->viewData($request));
    }

    public function reputation(Request $request)
    {
        return view('admin.qa.reputation', $this->viewData($request));
    }

    public function leaderboard(Request $request)
    {
        return view('admin.qa.leaderboard', $this->viewData($request));
    }

    public function leaderboardReport(Request $request)
    {
        return view('admin.qa.leaderboard-report', $this->viewData($request));
    }

    public function flagged(Request $request)
    {
        $request->merge(['status' => $request->get('flag_status') ?: 'flagged']);

        return view('admin.qa.flagged', $this->viewData($request));
    }

    public function storeQuestionDetailStatus(Request $request, int $question)
    {
        $status = $request->get('status');

        abort_unless(in_array($status, ['active', 'draft', 'unactive', 'pending', 'published', 'hidden', 'flagged'], true), 404);

        if (Schema::hasTable('questions') && in_array($status, ['pending', 'published', 'hidden', 'flagged'], true)) {
            DB::table('questions')->where('id', $question)->update(['status' => $status, 'updated_at' => now()]);
        }

        return back()->with('success', 'Demo question status saved.');
    }

    public function updateQuestionStatus(int $question, string $status)
    {
        abort_unless(in_array($status, ['published', 'hidden', 'flagged'], true), 404);

        if (Schema::hasTable('questions')) {
            DB::table('questions')->where('id', $question)->update(['status' => $status, 'updated_at' => now()]);
        }

        return back()->with('success', 'Question status updated.');
    }

    public function featureAnswer(Request $request, int $answer)
    {
        if (Schema::hasTable('answers')) {
            DB::table('answers')->where('id', $answer)->update([
                'is_admin_featured' => true,
                'confidence_level' => $request->get('confidence_level'),
                'admin_rank_order' => $request->integer('admin_rank_order') ?: null,
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Answer featured.');
    }

    public function unfeatureAnswer(int $answer)
    {
        if (Schema::hasTable('answers')) {
            DB::table('answers')->where('id', $answer)->update([
                'is_admin_featured' => false,
                'confidence_level' => null,
                'admin_rank_order' => null,
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Answer unfeatured.');
    }

    public function storeWeeklyTheme(Request $request)
    {
        if (Schema::hasTable('weekly_themes')) {
            DB::table('weekly_themes')->insert([
                'title' => (string) $request->string('title'),
                'description' => (string) $request->string('description'),
                'week_start_date' => $request->date('week_start_date'),
                'week_end_date' => $request->date('week_end_date'),
                'created_by_admin_id' => $request->user()->id,
                'status' => $request->get('status', 'active'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Weekly theme saved.');
    }

    public function updateWeeklyThemeStatus(int $weeklyTheme, string $status)
    {
        abort_unless(in_array($status, ['active', 'archived'], true), 404);

        if (Schema::hasTable('weekly_themes')) {
            DB::table('weekly_themes')->where('id', $weeklyTheme)->update(['status' => $status, 'updated_at' => now()]);
        }

        return back()->with('success', 'Weekly theme status updated.');
    }

    public function assignWeeklyThemeQuestion(Request $request, int $weeklyTheme)
    {
        $questionId = $request->integer('question_id');

        if (Schema::hasTable('questions') && Schema::hasTable('weekly_themes')) {
            DB::table('questions')->where('id', $questionId)->update([
                'weekly_theme_id' => $weeklyTheme,
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Question assigned to weekly theme.');
    }

    public function removeWeeklyThemeQuestion(int $weeklyTheme, int $question)
    {
        if (Schema::hasTable('questions')) {
            DB::table('questions')
                ->where('id', $question)
                ->where('weekly_theme_id', $weeklyTheme)
                ->update([
                    'weekly_theme_id' => null,
                    'updated_at' => now(),
                ]);
        }

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

        $points = (int) $validated['points'];
        $points = $validated['direction'] === 'negative' ? -$points : $points;

        if (Schema::hasTable('point_transactions')) {
            DB::table('point_transactions')->insert([
                'user_id' => (int) $validated['user_id'],
                'points' => $points,
                'source_type' => 'manual_adjustment',
                'source_id' => null,
                'reason' => $validated['reason'],
                'performed_by' => $request->user()->id,
                'created_at' => now(),
            ]);
        }

        if (Schema::hasTable('user_reputation')) {
            $existing = DB::table('user_reputation')->where('user_id', (int) $validated['user_id'])->first();
            $totalPoints = ($existing->total_points ?? 0) + $points;
            $rank = $this->starRankForPoints($totalPoints);

            $existing
                ? DB::table('user_reputation')->where('user_id', (int) $validated['user_id'])->update(['total_points' => $totalPoints, 'current_star_rank' => $rank, 'updated_at' => now()])
                : DB::table('user_reputation')->insert(['user_id' => (int) $validated['user_id'], 'total_points' => $totalPoints, 'current_star_rank' => $rank, 'updated_at' => now()]);
        }

        return back()->with('success', 'Reputation adjustment recorded.');
    }

    public function storeLeaderboardSettings(Request $request)
    {
        if (Schema::hasTable('leaderboard_settings')) {
            DB::table('leaderboard_settings')->insert([
                'min_points_threshold' => $request->integer('min_points_threshold'),
                'top_n' => $request->integer('top_n'),
                'effective_from' => $request->date('effective_from'),
            ]);
        }

        return back()->with('success', 'Leaderboard settings saved.');
    }

    public function snapshotLeaderboard(Request $request)
    {
        if (Schema::hasTable('monthly_leaderboard_snapshots') && Schema::hasTable('user_reputation')) {
            $yearMonth = $request->get('year_month', now()->format('Y-m'));
            $settings = $this->settings();

            DB::table('monthly_leaderboard_snapshots')->where('year_month', $yearMonth)->delete();
            DB::table('user_reputation')
                ->where('total_points', '>=', $settings['min_points_threshold'])
                ->orderByDesc('total_points')
                ->limit($settings['top_n'])
                ->get()
                ->each(fn ($row, $index) => DB::table('monthly_leaderboard_snapshots')->insert([
                    'user_id' => $row->user_id,
                    'year_month' => $yearMonth,
                    'total_points_in_month' => $row->total_points,
                    'rank_position' => $index + 1,
                    'created_at' => now(),
                ]));
        }

        return back()->with('success', 'Leaderboard snapshot refreshed.');
    }

    private function viewData(Request $request): array
    {
        return [
            ...$this->filters(),
            'stats' => $this->stats(),
            'questions' => $this->questionsData($request),
            'answers' => $this->answersData($request),
            'themes' => $this->themes(),
            'themeAssignments' => $this->themeAssignments(),
            'assignableQuestions' => $this->assignableQuestions(),
            'leaders' => $this->leaders($request->get('year_month')),
            'settings' => $this->settings(),
            'ledger' => $this->ledger($request),
            'reputationUsers' => $this->reputationUsers($request),
            'users' => $this->users(),
            'filters' => $request->only(['status', 'plant_type_id', 'weekly_theme_id', 'keyword', 'date_from', 'date_to', 'question_id', 'is_admin_featured', 'year_month', 'source_type', 'flag_status']),
        ];
    }

    private function filters(): array
    {
        $plantTypes = Schema::hasTable('plant_types')
            ? DB::table('plant_types')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        $weeklyThemes = Schema::hasTable('weekly_themes')
            ? DB::table('weekly_themes')->orderByDesc('week_start_date')->get()
            : collect();

        return [
            'plantTypes' => $plantTypes->isNotEmpty() ? $plantTypes : collect($this->demoPlantTypes()),
            'weeklyThemes' => $weeklyThemes->isNotEmpty() ? $weeklyThemes : collect($this->demoThemes()),
        ];
    }

    private function stats(): array
    {
        $stats = [
            ['label' => 'Pending Review', 'value' => Schema::hasTable('questions') ? DB::table('questions')->where('status', 'pending')->count() : 0, 'color' => 'warning', 'icon' => 'bi bi-hourglass-split'],
            ['label' => 'Published', 'value' => Schema::hasTable('questions') ? DB::table('questions')->where('status', 'published')->count() : 0, 'color' => 'success', 'icon' => 'bi bi-check2-circle'],
            ['label' => 'Flagged', 'value' => Schema::hasTable('questions') ? DB::table('questions')->where('status', 'flagged')->count() : 0, 'color' => 'danger', 'icon' => 'bi bi-flag'],
            ['label' => 'Answers', 'value' => Schema::hasTable('answers') ? DB::table('answers')->count() : 0, 'color' => 'info', 'icon' => 'bi bi-chat-dots'],
        ];

        return collect($stats)->sum('value') > 0 ? $stats : [
            ['label' => 'Pending Review', 'value' => 5, 'color' => 'warning', 'icon' => 'bi bi-hourglass-split'],
            ['label' => 'Published', 'value' => 18, 'color' => 'success', 'icon' => 'bi bi-check2-circle'],
            ['label' => 'Flagged', 'value' => 3, 'color' => 'danger', 'icon' => 'bi bi-flag'],
            ['label' => 'Answers', 'value' => 12, 'color' => 'info', 'icon' => 'bi bi-chat-dots'],
        ];
    }

    private function questionsData(Request $request)
    {
        if (! Schema::hasTable('questions')) {
            return $this->filteredDemoQuestions($request);
        }

        $hasQuestions = DB::table('questions')->exists();

        $questions = DB::table('questions')
            ->leftJoin('users', 'users.id', '=', 'questions.user_id')
            ->leftJoin('plant_types', 'plant_types.id', '=', 'questions.plant_type_id')
            ->leftJoin('weekly_themes', 'weekly_themes.id', '=', 'questions.weekly_theme_id')
            ->select('questions.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'plant_types.name as plant_name', 'weekly_themes.title as theme_title')
            ->when($request->get('status'), fn ($query, $status) => $query->where('questions.status', $status))
            ->when($request->integer('plant_type_id'), fn ($query, $plantTypeId) => $query->where('questions.plant_type_id', $plantTypeId))
            ->when($request->integer('weekly_theme_id'), fn ($query, $weeklyThemeId) => $query->where('questions.weekly_theme_id', $weeklyThemeId))
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('keyword')->trim().'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('questions.title', 'like', $keyword)
                        ->orWhere('questions.body', 'like', $keyword)
                        ->orWhere('users.username', 'like', $keyword)
                        ->orWhere('users.first_name', 'like', $keyword)
                        ->orWhere('users.last_name', 'like', $keyword);
                });
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('questions.created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('questions.created_at', '<=', $request->date('date_to')))
            ->latest('questions.created_at')
            ->limit(50)
            ->get()
            ->map(fn ($question): array => [
                'id' => $question->id,
                'title' => $question->title,
                'plant' => $question->plant_name ?: 'General',
                'theme' => $question->theme_title ?: 'Open discussion',
                'weekly_theme_id' => $question->weekly_theme_id,
                'status' => $question->status,
                'status_color' => ['published' => 'success', 'pending' => 'warning', 'flagged' => 'danger', 'hidden' => 'secondary'][$question->status] ?? 'light',
                'author' => $question->is_anonymous ? 'Anonymous' : $this->userDisplayName($question, $question->user_id),
                'domains' => $this->domains($question->id),
                'answer_count' => $this->answerCount((int) $question->id),
                'body' => Str::limit($question->body, 220),
                'created_at' => $question->created_at,
            ]);

        return $questions->isNotEmpty() || $hasQuestions ? $questions : $this->filteredDemoQuestions($request);
    }

    private function answersData(Request $request)
    {
        if (! Schema::hasTable('answers')) {
            return collect($this->demoAnswers());
        }

        $hasAnswers = DB::table('answers')->exists();

        $answers = DB::table('answers')
            ->leftJoin('questions', 'questions.id', '=', 'answers.question_id')
            ->leftJoin('users', 'users.id', '=', 'answers.user_id')
            ->select('answers.*', 'questions.title as question_title', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
            ->when($request->integer('question_id'), fn ($query, $questionId) => $query->where('answers.question_id', $questionId))
            ->when($request->has('is_admin_featured'), fn ($query) => $query->where('answers.is_admin_featured', $request->boolean('is_admin_featured')))
            ->latest('answers.created_at')
            ->limit(50)
            ->get()
            ->map(fn ($answer): array => [
                'id' => $answer->id,
                'body' => Str::limit($answer->body, 120),
                'question' => $answer->question_title ?: 'Question #'.$answer->question_id,
                'author' => $answer->is_anonymous ? 'Anonymous' : $this->userDisplayName($answer, $answer->user_id),
                'confidence' => $answer->confidence_level ?: 'unrated',
                'featured' => (bool) $answer->is_admin_featured,
                'rank' => $answer->admin_rank_order ?: '-',
            ]);

        return $answers->isNotEmpty() || $hasAnswers ? $answers : collect($this->demoAnswers());
    }

    private function answerCount(int $questionId): int
    {
        return Schema::hasTable('answers')
            ? DB::table('answers')->where('question_id', $questionId)->count()
            : 0;
    }

    private function questionDetailData(int $questionId): array
    {
        if (Schema::hasTable('questions')) {
            $question = DB::table('questions')
                ->leftJoin('users', 'users.id', '=', 'questions.user_id')
                ->leftJoin('plant_types', 'plant_types.id', '=', 'questions.plant_type_id')
                ->leftJoin('weekly_themes', 'weekly_themes.id', '=', 'questions.weekly_theme_id')
                ->select('questions.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'plant_types.name as plant_name', 'weekly_themes.title as theme_title')
                ->where('questions.id', $questionId)
                ->first();

            if ($question) {
                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'body' => $question->body,
                    'plant' => $question->plant_name ?: 'General',
                    'theme' => $question->theme_title ?: 'Open discussion',
                    'weekly_theme_id' => $question->weekly_theme_id,
                    'status' => $question->status,
                    'status_color' => ['published' => 'success', 'pending' => 'warning', 'flagged' => 'danger', 'hidden' => 'secondary', 'active' => 'success', 'draft' => 'warning', 'unactive' => 'secondary'][$question->status] ?? 'light',
                    'author' => $question->is_anonymous ? 'Anonymous' : $this->userDisplayName($question, $question->user_id),
                    'author_role' => $question->is_anonymous ? 'Anonymous poster' : 'Community member',
                    'author_email' => $question->is_anonymous ? 'Hidden for public view' : ($question->user_email ?: 'No email recorded'),
                    'author_meta' => $question->is_anonymous ? 'Identity retained for admin moderation' : 'Verified user account',
                    'domains' => $this->domains($question->id),
                    'created_at' => $question->created_at,
                ];
            }
        }

        return collect($this->demoQuestionDetails())->firstWhere('id', $questionId) ?: $this->demoQuestionDetails()[0];
    }

    private function questionAnswersData(int $questionId)
    {
        if (Schema::hasTable('answers')) {
            $answers = DB::table('answers')
                ->leftJoin('users', 'users.id', '=', 'answers.user_id')
                ->select('answers.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
                ->where('answers.question_id', $questionId)
                ->latest('answers.created_at')
                ->get()
                ->map(fn ($answer): array => [
                    'id' => $answer->id,
                    'body' => $answer->body,
                    'author' => $answer->is_anonymous ? 'Anonymous' : $this->userDisplayName($answer, $answer->user_id),
                    'confidence' => $answer->confidence_level ?: 'unrated',
                    'featured' => (bool) $answer->is_admin_featured,
                    'rank' => $answer->admin_rank_order ?: '-',
                ]);

            if ($answers->isNotEmpty() || (Schema::hasTable('questions') && DB::table('questions')->where('id', $questionId)->exists())) {
                return $answers;
            }
        }

        return collect($this->demoQuestionAnswers());
    }

    private function domains(int $questionId): string
    {
        return Schema::hasTable('question_domain_links')
            ? DB::table('question_domain_links')
                ->join('knowledge_domains', 'knowledge_domains.id', '=', 'question_domain_links.knowledge_domain_id')
                ->where('question_domain_links.question_id', $questionId)
                ->orderBy('knowledge_domains.name')
                ->pluck('knowledge_domains.name')
                ->implode(', ')
            : '';
    }

    private function themes()
    {
        if (! Schema::hasTable('weekly_themes')) {
            return collect($this->demoThemes());
        }

        $themes = DB::table('weekly_themes')
            ->when(Schema::hasTable('questions'), function ($query): void {
                $query->leftJoin('questions', 'questions.weekly_theme_id', '=', 'weekly_themes.id')
                    ->select('weekly_themes.*', DB::raw('count(questions.id) as assigned_questions_count'))
                    ->groupBy('weekly_themes.id');
            })
            ->orderByDesc('week_start_date')
            ->limit(30)
            ->get()
            ->map(function (object $theme): object {
                $theme->assigned_questions_count ??= 0;

                return $theme;
            });

        return $themes->isNotEmpty() ? $themes : collect($this->demoThemes());
    }

    private function themeAssignments()
    {
        if (! Schema::hasTable('questions')) {
            return collect($this->demoQuestions())->groupBy('weekly_theme_id');
        }

        return $this->questionsData(new Request)->groupBy('weekly_theme_id');
    }

    private function assignableQuestions()
    {
        if (! Schema::hasTable('questions')) {
            return collect($this->demoQuestions());
        }

        return $this->questionsData(new Request);
    }

    private function leaders(?string $yearMonth = null)
    {
        $yearMonth ??= now()->format('Y-m');

        if (! Schema::hasTable('monthly_leaderboard_snapshots')) {
            return collect($this->demoLeaders());
        }

        $leaders = DB::table('monthly_leaderboard_snapshots')
            ->leftJoin('users', 'users.id', '=', 'monthly_leaderboard_snapshots.user_id')
            ->select('monthly_leaderboard_snapshots.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
            ->where('year_month', $yearMonth)
            ->orderBy('rank_position')
            ->limit(25)
            ->get()
            ->map(fn ($leader): array => [
                'rank' => $leader->rank_position,
                'name' => $this->userDisplayName($leader, $leader->user_id),
                'points' => $leader->total_points_in_month,
                'stars' => null,
            ]);

        if ($leaders->isNotEmpty()) {
            return $leaders;
        }

        if (Schema::hasTable('user_reputation')) {
            $leaders = DB::table('user_reputation')
                ->leftJoin('users', 'users.id', '=', 'user_reputation.user_id')
                ->select('user_reputation.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
                ->orderByDesc('user_reputation.total_points')
                ->limit(25)
                ->get()
                ->map(fn ($leader, $index): array => [
                    'rank' => $index + 1,
                    'name' => $this->userDisplayName($leader, $leader->user_id),
                    'points' => $leader->total_points,
                    'stars' => $leader->current_star_rank,
                ]);
        }

        return $leaders->isNotEmpty() ? $leaders : collect($this->demoLeaders());
    }

    private function settings(): array
    {
        $settings = Schema::hasTable('leaderboard_settings')
            ? DB::table('leaderboard_settings')->orderByDesc('effective_from')->first()
            : null;

        return [
            'min_points_threshold' => $settings->min_points_threshold ?? 100,
            'top_n' => $settings->top_n ?? 10,
            'effective_from' => $settings->effective_from ?? now()->toDateString(),
        ];
    }

    private function ledger(Request $request)
    {
        if (! Schema::hasTable('point_transactions')) {
            return $this->filteredDemoLedger($request);
        }

        $ledger = DB::table('point_transactions')
            ->leftJoin('users', 'users.id', '=', 'point_transactions.user_id')
            ->leftJoin('users as performers', 'performers.id', '=', 'point_transactions.performed_by')
            ->select('point_transactions.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'performers.username as performer_username', 'performers.first_name as performer_first_name', 'performers.last_name as performer_last_name', 'performers.email as performer_email')
            ->when($request->get('source_type'), fn ($query, $sourceType) => $query->where('point_transactions.source_type', $sourceType))
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('keyword')->trim().'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('point_transactions.reason', 'like', $keyword)
                        ->orWhere('users.username', 'like', $keyword)
                        ->orWhere('users.first_name', 'like', $keyword)
                        ->orWhere('users.last_name', 'like', $keyword)
                        ->orWhere('users.email', 'like', $keyword);
                });
            })
            ->latest('point_transactions.created_at')
            ->limit(30)
            ->get()
            ->map(function (object $entry): object {
                $entry->display_name = $this->userDisplayName($entry, $entry->user_id);
                $entry->performed_by_name = trim(implode(' ', array_filter([$entry->performer_first_name ?? null, $entry->performer_last_name ?? null])))
                    ?: ($entry->performer_username ?? $entry->performer_email ?? 'System');

                return $entry;
            });

        return $ledger->isNotEmpty() ? $ledger : $this->filteredDemoLedger($request);
    }

    private function users()
    {
        if (! Schema::hasTable('users')) {
            return collect($this->demoUsers());
        }

        $users = DB::table('users')->orderBy('username')->limit(100)->get(['id', 'username', 'first_name', 'last_name', 'email'])->map(function (object $user): object {
            $user->display_name = $this->userDisplayName($user);

            return $user;
        });

        return $users->isNotEmpty() ? $users : collect($this->demoUsers());
    }

    private function reputationUsers(Request $request)
    {
        if (! Schema::hasTable('user_reputation')) {
            return $this->filteredDemoReputationUsers($request);
        }

        $users = DB::table('user_reputation')
            ->leftJoin('users', 'users.id', '=', 'user_reputation.user_id')
            ->select('user_reputation.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('keyword')->trim().'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('users.username', 'like', $keyword)
                        ->orWhere('users.first_name', 'like', $keyword)
                        ->orWhere('users.last_name', 'like', $keyword)
                        ->orWhere('users.email', 'like', $keyword);
                });
            })
            ->orderByDesc('user_reputation.total_points')
            ->limit(50)
            ->get()
            ->map(function (object $user): object {
                $user->display_name = $this->userDisplayName($user, $user->user_id);
                $user->email = $user->user_email ?: 'No email recorded';

                return $user;
            });

        return $users->isNotEmpty() ? $users : $this->filteredDemoReputationUsers($request);
    }

    private function filteredDemoQuestions(Request $request)
    {
        return collect($this->demoQuestions())
            ->when($request->get('status'), fn ($questions, $status) => $questions->where('status', $status))
            ->when($request->integer('plant_type_id'), fn ($questions, $plantTypeId) => $questions->where('plant_type_id', $plantTypeId))
            ->when($request->integer('weekly_theme_id'), fn ($questions, $weeklyThemeId) => $questions->where('weekly_theme_id', $weeklyThemeId))
            ->when($request->filled('keyword'), function ($questions) use ($request) {
                $keyword = Str::lower((string) $request->string('keyword')->trim());

                return $questions->filter(fn (array $question): bool => Str::contains(Str::lower(implode(' ', [
                    $question['title'],
                    $question['body'] ?? '',
                    $question['plant'],
                    $question['theme'],
                    $question['author'],
                    $question['domains'],
                ])), $keyword));
            })
            ->when($request->filled('date_from'), fn ($questions) => $questions->filter(fn (array $question): bool => strtotime($question['created_at']) >= strtotime((string) $request->date('date_from'))))
            ->when($request->filled('date_to'), fn ($questions) => $questions->filter(fn (array $question): bool => strtotime($question['created_at']) <= strtotime((string) $request->date('date_to')->endOfDay())));
    }

    private function filteredDemoLedger(Request $request)
    {
        return collect($this->demoLedger())
            ->when($request->get('source_type'), fn ($ledger, $sourceType) => $ledger->where('source_type', $sourceType))
            ->when($request->filled('keyword'), function ($ledger) use ($request) {
                $keyword = Str::lower((string) $request->string('keyword')->trim());

                return $ledger->filter(fn (object $entry): bool => Str::contains(Str::lower(implode(' ', [
                    $entry->display_name,
                    $entry->user_email,
                    $entry->source_type,
                    $entry->reason,
                    $entry->performed_by_name,
                ])), $keyword));
            });
    }

    private function filteredDemoReputationUsers(Request $request)
    {
        return collect($this->demoReputationUsers())
            ->when($request->filled('keyword'), function ($users) use ($request) {
                $keyword = Str::lower((string) $request->string('keyword')->trim());

                return $users->filter(fn (object $user): bool => Str::contains(Str::lower(implode(' ', [
                    $user->display_name,
                    $user->email,
                    $user->username,
                ])), $keyword));
            });
    }

    private function starRankForPoints(int $points): int
    {
        return match (true) {
            $points >= 1200 => 5,
            $points >= 800 => 4,
            $points >= 400 => 3,
            $points >= 100 => 2,
            default => 1,
        };
    }

    private function demoPlantTypes(): array
    {
        return [
            (object) ['id' => 1, 'name' => 'Ammonia plant'],
            (object) ['id' => 2, 'name' => 'Urea synthesis'],
            (object) ['id' => 3, 'name' => 'Utilities and steam'],
        ];
    }

    private function demoQuestionDetails(): array
    {
        return [
            ['id' => 9101, 'title' => 'Demo: compressor vibration after startup', 'body' => 'The compressor train shows rising vibration during the first hour after startup. Operators need guidance on checks before the next load increase.', 'plant' => 'Ammonia plant', 'theme' => 'Rotating equipment reliability', 'weekly_theme_id' => 9301, 'status' => 'active', 'status_color' => 'success', 'author' => 'Aisha Tran', 'author_role' => 'Community member', 'author_email' => 'aisha.demo@example.test', 'author_meta' => 'Demo profile with verified operations background', 'domains' => 'Troubleshooting, Maintenance', 'answer_count' => 2, 'created_at' => now()->subHours(6)->format('Y-m-d H:i')],
            ['id' => 9102, 'title' => 'Demo: steam trap losses during day shift', 'body' => 'Condensate return has dropped and the day shift suspects failed steam traps around the synthesis area.', 'plant' => 'Urea synthesis', 'theme' => 'Energy optimization', 'weekly_theme_id' => 9302, 'status' => 'draft', 'status_color' => 'warning', 'author' => 'Anonymous', 'author_role' => 'Anonymous poster', 'author_email' => 'Hidden for public view', 'author_meta' => 'Identity retained for admin moderation', 'domains' => 'Energy, Operations', 'answer_count' => 1, 'created_at' => now()->subDay()->format('Y-m-d H:i')],
            ['id' => 9103, 'title' => 'Demo: exchanger fouling trend needs review', 'body' => 'Approach temperature is drifting and the team needs a quick moderation decision before public discussion continues.', 'plant' => 'Utilities and steam', 'theme' => 'Open discussion', 'weekly_theme_id' => null, 'status' => 'unactive', 'status_color' => 'secondary', 'author' => 'Minh Nguyen', 'author_role' => 'Community member', 'author_email' => 'minh.demo@example.test', 'author_meta' => 'Demo profile with maintenance review history', 'domains' => 'Heat exchange', 'answer_count' => 3, 'created_at' => now()->subDays(2)->format('Y-m-d H:i')],
        ];
    }

    private function demoQuestionAnswers(): array
    {
        return [
            ['id' => 9201, 'body' => 'Trend the bearing temperature with the vibration spectrum, then inspect coupling alignment before raising load.', 'author' => 'Carlos Rivera', 'confidence' => 'high', 'featured' => true, 'rank' => 1],
            ['id' => 9203, 'body' => 'Compare the startup profile against the last clean run and verify the lube oil differential pressure.', 'author' => 'Aisha Tran', 'confidence' => 'medium', 'featured' => false, 'rank' => '-'],
        ];
    }

    private function demoWarningHistory(): array
    {
        return [
            ['date' => now()->subHours(3)->format('Y-m-d H:i'), 'status' => 'draft', 'note' => 'Demo: asked author to add operating pressure and startup timeline.'],
            ['date' => now()->subDay()->format('Y-m-d H:i'), 'status' => 'active', 'note' => 'Demo: moderator restored the question after domain review.'],
        ];
    }

    private function demoQuestions(): array
    {
        return [
            ['id' => 9101, 'title' => 'Demo: compressor vibration after startup', 'body' => 'Compressor train vibration increased after startup and needs operating context before approval.', 'plant_type_id' => 1, 'plant' => 'Ammonia plant', 'theme' => 'Rotating equipment reliability', 'weekly_theme_id' => 9301, 'status' => 'pending', 'status_color' => 'warning', 'author' => 'Aisha Tran', 'domains' => 'Troubleshooting, Maintenance', 'answer_count' => 2, 'created_at' => now()->subHours(6)->format('Y-m-d H:i')],
            ['id' => 9102, 'title' => 'Demo: steam trap losses during day shift', 'body' => 'Steam trap loss report from synthesis area with condensate return notes.', 'plant_type_id' => 2, 'weekly_theme_id' => 9302, 'plant' => 'Urea synthesis', 'theme' => 'Energy optimization quick wins', 'status' => 'published', 'status_color' => 'success', 'author' => 'Anonymous', 'domains' => 'Energy, Operations', 'answer_count' => 1, 'created_at' => now()->subDay()->format('Y-m-d H:i')],
            ['id' => 9103, 'title' => 'Demo: exchanger fouling trend needs review', 'body' => 'Exchanger approach temperature trend needs flagged content review.', 'plant_type_id' => 3, 'weekly_theme_id' => 9302, 'plant' => 'Utilities and steam', 'theme' => 'Energy optimization quick wins', 'status' => 'flagged', 'status_color' => 'danger', 'author' => 'Minh Nguyen', 'domains' => 'Heat exchange', 'answer_count' => 3, 'created_at' => now()->subDays(2)->format('Y-m-d H:i')],
            ['id' => 9104, 'title' => 'Demo: analyzer drift before catalyst change', 'body' => 'Analyzer drift question hidden until calibration evidence is attached.', 'plant_type_id' => 1, 'weekly_theme_id' => 9303, 'plant' => 'Ammonia plant', 'theme' => 'Process safety near misses', 'status' => 'hidden', 'status_color' => 'secondary', 'author' => 'Priya Shah', 'domains' => 'Process control', 'answer_count' => 0, 'created_at' => now()->subDays(5)->format('Y-m-d H:i')],
        ];
    }

    private function demoAnswers(): array
    {
        return [
            ['id' => 9201, 'body' => 'Demo answer: trend the bearing temperature with the vibration spectrum, then inspect coupling alignment before raising load.', 'question' => 'Compressor vibration after startup', 'author' => 'Carlos Rivera', 'confidence' => 'high', 'featured' => true, 'rank' => 1],
            ['id' => 9202, 'body' => 'Demo answer: isolate the suspected steam traps and calculate condensate loss against the operating pressure.', 'question' => 'Steam trap losses during day shift', 'author' => 'Anonymous', 'confidence' => 'medium', 'featured' => false, 'rank' => '-'],
            ['id' => 9203, 'body' => 'Demo answer: compare exchanger approach temperature against clean baseline and validate the flow transmitter first.', 'question' => 'Exchanger fouling trend needs review', 'author' => 'Aisha Tran', 'confidence' => 'high', 'featured' => true, 'rank' => 2],
            ['id' => 9204, 'body' => 'Demo answer: review calibration history and sample line heat tracing before replacing analyzer hardware.', 'question' => 'Analyzer drift before catalyst change', 'author' => 'Minh Nguyen', 'confidence' => 'low', 'featured' => false, 'rank' => '-'],
        ];
    }

    private function demoThemes(): array
    {
        return [
            (object) ['id' => 9301, 'title' => 'Rotating equipment reliability', 'description' => 'Demo theme for pumps, compressors, and turbines.', 'week_start_date' => now()->startOfWeek()->toDateString(), 'week_end_date' => now()->endOfWeek()->toDateString(), 'status' => 'active', 'assigned_questions_count' => 1],
            (object) ['id' => 9302, 'title' => 'Energy optimization quick wins', 'description' => 'Demo theme for steam, condensate, and utilities savings.', 'week_start_date' => now()->subWeek()->startOfWeek()->toDateString(), 'week_end_date' => now()->subWeek()->endOfWeek()->toDateString(), 'status' => 'archived', 'assigned_questions_count' => 2],
            (object) ['id' => 9303, 'title' => 'Process safety near misses', 'description' => 'Demo theme for lessons learned and reporting quality.', 'week_start_date' => now()->addWeek()->startOfWeek()->toDateString(), 'week_end_date' => now()->addWeek()->endOfWeek()->toDateString(), 'status' => 'active', 'assigned_questions_count' => 1],
        ];
    }

    private function demoLeaders(): array
    {
        return [
            ['rank' => 1, 'name' => 'Aisha Tran', 'points' => 1480, 'stars' => 5],
            ['rank' => 2, 'name' => 'Minh Nguyen', 'points' => 1265, 'stars' => 4],
            ['rank' => 3, 'name' => 'Carlos Rivera', 'points' => 980, 'stars' => 4],
            ['rank' => 4, 'name' => 'Priya Shah', 'points' => 845, 'stars' => 3],
        ];
    }

    private function demoReputationUsers(): array
    {
        return [
            (object) ['id' => 9401, 'user_id' => 9401, 'username' => 'aisha.tran', 'display_name' => 'Aisha Tran', 'email' => 'aisha.demo@example.test', 'total_points' => 1480, 'current_star_rank' => 5],
            (object) ['id' => 9402, 'user_id' => 9402, 'username' => 'minh.nguyen', 'display_name' => 'Minh Nguyen', 'email' => 'minh.demo@example.test', 'total_points' => 1265, 'current_star_rank' => 4],
            (object) ['id' => 9403, 'user_id' => 9403, 'username' => 'carlos.rivera', 'display_name' => 'Carlos Rivera', 'email' => 'carlos.demo@example.test', 'total_points' => 980, 'current_star_rank' => 4],
            (object) ['id' => 9404, 'user_id' => 9404, 'username' => 'priya.shah', 'display_name' => 'Priya Shah', 'email' => 'priya.demo@example.test', 'total_points' => 845, 'current_star_rank' => 3],
        ];
    }

    private function demoLedger(): array
    {
        return [
            (object) ['user_id' => 9401, 'display_name' => 'Aisha Tran', 'user_first_name' => 'Aisha', 'user_last_name' => 'Tran', 'user_username' => 'aisha.tran', 'user_email' => 'aisha.demo@example.test', 'points' => 45, 'source_type' => 'answer', 'reason' => 'Demo: featured answer on vibration troubleshooting', 'performed_by_name' => 'System'],
            (object) ['user_id' => 9402, 'display_name' => 'Minh Nguyen', 'user_first_name' => 'Minh', 'user_last_name' => 'Nguyen', 'user_username' => 'minh.nguyen', 'user_email' => 'minh.demo@example.test', 'points' => 25, 'source_type' => 'question', 'reason' => 'Demo: high-quality question with domain links', 'performed_by_name' => 'System'],
            (object) ['user_id' => 9403, 'display_name' => 'Carlos Rivera', 'user_first_name' => 'Carlos', 'user_last_name' => 'Rivera', 'user_username' => 'carlos.rivera', 'user_email' => 'carlos.demo@example.test', 'points' => -10, 'source_type' => 'manual_adjustment', 'reason' => 'Demo: duplicate answer adjustment', 'performed_by_name' => 'QA Admin'],
        ];
    }

    private function demoUsers(): array
    {
        return [
            (object) ['id' => 9401, 'username' => 'aisha.tran', 'first_name' => 'Aisha', 'last_name' => 'Tran', 'email' => 'aisha.demo@example.test', 'display_name' => 'Aisha Tran'],
            (object) ['id' => 9402, 'username' => 'minh.nguyen', 'first_name' => 'Minh', 'last_name' => 'Nguyen', 'email' => 'minh.demo@example.test', 'display_name' => 'Minh Nguyen'],
            (object) ['id' => 9403, 'username' => 'carlos.rivera', 'first_name' => 'Carlos', 'last_name' => 'Rivera', 'email' => 'carlos.demo@example.test', 'display_name' => 'Carlos Rivera'],
        ];
    }

    private function userDisplayName(object $row, ?int $userId = null): string
    {
        $firstName = $row->user_first_name ?? $row->first_name ?? null;
        $lastName = $row->user_last_name ?? $row->last_name ?? null;
        $username = $row->user_username ?? $row->username ?? null;
        $email = $row->user_email ?? $row->email ?? null;
        $fullName = trim(implode(' ', array_filter([$firstName, $lastName])));

        return $fullName
            ?: ($username ?? null)
            ?: ($email ?? null)
            ?: ($userId ? 'Member #'.$userId : 'Member');
    }
}
