<?php

namespace App\Services\Admin;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QaDashboardViewDataService
{

    public function updateQuestionStatus(int $questionId, string $status): void
    {
        if (Schema::hasTable('questions')) {
            DB::table('questions')->where('id', $questionId)->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
        }
    }

    public function featureAnswer(int $answerId, array $data): void
    {
        if (Schema::hasTable('answers')) {
            DB::table('answers')->where('id', $answerId)->update([
                'is_admin_featured' => true,
                'confidence_level' => $data['confidence_level'] ?? null,
                'admin_rank_order' => $data['admin_rank_order'] ?? null,
                'updated_at' => now(),
            ]);
        }
    }

    public function unfeatureAnswer(int $answerId): void
    {
        if (Schema::hasTable('answers')) {
            DB::table('answers')->where('id', $answerId)->update([
                'is_admin_featured' => false,
                'confidence_level' => null,
                'admin_rank_order' => null,
                'updated_at' => now(),
            ]);
        }
    }

    public function storeWeeklyTheme(array $data): void
    {
        if (Schema::hasTable('weekly_themes')) {
            DB::table('weekly_themes')->insert([
                'title' => $data['title'] ?? '',
                'description' => $data['description'] ?? '',
                'week_start_date' => $data['week_start_date'] ?? null,
                'week_end_date' => $data['week_end_date'] ?? null,
                'created_by_admin_id' => $data['created_by_admin_id'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function updateWeeklyThemeStatus(int $weeklyThemeId, string $status): void
    {
        if (Schema::hasTable('weekly_themes')) {
            DB::table('weekly_themes')->where('id', $weeklyThemeId)->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
        }
    }

    public function assignWeeklyThemeQuestion(int $weeklyThemeId, int $questionId): void
    {
        if (Schema::hasTable('questions') && Schema::hasTable('weekly_themes')) {
            DB::table('questions')->where('id', $questionId)->update([
                'weekly_theme_id' => $weeklyThemeId,
                'updated_at' => now(),
            ]);
        }
    }

    public function removeWeeklyThemeQuestion(int $weeklyThemeId, int $questionId): void
    {
        if (Schema::hasTable('questions')) {
            DB::table('questions')
                ->where('id', $questionId)
                ->where('weekly_theme_id', $weeklyThemeId)
                ->update([
                    'weekly_theme_id' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function storeReputationAdjustment(array $data, int $actorId): void
    {
        $points = (int) $data['points'];
        $points = $data['direction'] === 'negative' ? -$points : $points;
        $userId = (int) $data['user_id'];

        DB::transaction(function () use ($actorId, $data, $points, $userId): void {
            if (Schema::hasTable('point_transactions')) {
                DB::table('point_transactions')->insert([
                    'user_id' => $userId,
                    'points' => $points,
                    'source_type' => 'manual_adjustment',
                    'source_id' => null,
                    'reason' => $data['reason'],
                    'performed_by' => $actorId,
                    'created_at' => now(),
                ]);
            }

            if (Schema::hasTable('user_reputation')) {
                $existing = DB::table('user_reputation')->where('user_id', $userId)->first();
                $totalPoints = ($existing->total_points ?? 0) + $points;
                $rank = $this->starRankForPoints($totalPoints);

                $existing
                    ? DB::table('user_reputation')->where('user_id', $userId)->update(['total_points' => $totalPoints, 'current_star_rank' => $rank, 'updated_at' => now()])
                    : DB::table('user_reputation')->insert(['user_id' => $userId, 'total_points' => $totalPoints, 'current_star_rank' => $rank, 'updated_at' => now()]);
            }
        });
    }

    public function storeLeaderboardSettings(array $data): void
    {
        if (Schema::hasTable('leaderboard_settings')) {
            DB::table('leaderboard_settings')->insert([
                'min_points_threshold' => $data['min_points_threshold'] ?? null,
                'top_n' => $data['top_n'] ?? null,
                'effective_from' => $data['effective_from'] ?? null,
            ]);
        }
    }

    public function snapshotLeaderboard(string $yearMonth): void
    {
        if (Schema::hasTable('monthly_leaderboard_snapshots') && Schema::hasTable('user_reputation')) {
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
    }

    public function data(array $filters): array
    {
        $filters = $this->onlyFilters($filters);
        $questions = $this->questionsData($filters);
        $qaTabCounts = $this->qaTabCounts();

        return [
            ...$this->filters(),
            ...$this->listingData($questions, $filters, $qaTabCounts),
            'stats' => $this->stats(),
            'questions' => $questions,
            'qaTabCounts' => $qaTabCounts,
            'answers' => $this->answersData($filters),
            'themes' => $this->themes(),
            'themeAssignments' => $this->themeAssignments(),
            'assignableQuestions' => $this->assignableQuestions(),
            'leaders' => $this->leaders($this->filter($filters, 'year_month')),
            'settings' => $this->settings(),
            'ledger' => $this->ledger($filters),
            'reputationUsers' => $this->reputationUsers($filters),
            'users' => $this->users(),
            'filters' => $filters,
        ];
    }

    public function createQuestionData(array $filters = []): array
    {
        return [
            ...$this->filters(),
            'filters' => $this->onlyFilters($filters),
            'users' => $this->users(),
            'knowledgeDomains' => $this->knowledgeDomains(),
            'mediaFiles' => $this->mediaFiles(),
            'partnerProfiles' => $this->partnerProfiles(),
            'questionModes' => [
                ['value' => 'admin_seed', 'label' => 'Admin Seed Question', 'description' => 'Admin-authored seed content for the Q&A workflow.'],
                ['value' => 'admin_on_behalf', 'label' => 'Admin On Behalf', 'description' => 'Admin posts for a partner and records partner attribution.'],
                ['value' => 'community', 'label' => 'Community', 'description' => 'Normal community-created question mode.'],
            ],
            'statusOptions' => [
                ['value' => 'pending', 'label' => 'Pending Approval'],
                ['value' => 'published', 'label' => 'Active'],
                ['value' => 'hidden', 'label' => 'Blocked'],
                ['value' => 'flagged', 'label' => 'Flagged'],
            ],
        ];
    }


    private function listingData($questions, array $filters, array $qaTabCounts): array
    {
        $activeTab = $this->filter($filters, 'tab', 'all') ?: 'all';
        $totalQuestions = (int) ($qaTabCounts['all'] ?? $questions->count());
        $unansweredCount = (int) ($qaTabCounts['open'] ?? $questions->where('answer_count', 0)->count());
        $pendingCount = (int) ($qaTabCounts['pending'] ?? $questions->where('status', 'pending')->count());
        $anonymousCount = $questions->where('is_anonymous', true)->count();
        $anonymousPercent = $totalQuestions > 0 ? round(($anonymousCount / $totalQuestions) * 100) : 0;
        $publishedQuestions = $questions->reject(fn (array $question): bool => ($question['status'] ?? null) === 'pending')->values();
        $pendingQuestions = $questions->filter(fn (array $question): bool => ($question['status'] ?? null) === 'pending')->values();

        return [
            'activeTab' => $activeTab,
            'totalQuestions' => $totalQuestions,
            'publishedQuestions' => $this->listingRows($publishedQuestions),
            'pendingQuestions' => $this->listingRows($pendingQuestions),
            'visiblePublished' => $activeTab === 'pending' ? 0 : $publishedQuestions->count(),
            'visiblePending' => $activeTab === 'pending' ? $pendingQuestions->count() : $pendingCount,
            'qaStatCards' => [
                ['label' => 'Total Questions', 'value' => number_format($totalQuestions), 'sub' => 'Across current filters', 'icon_class' => 'qa-stat-icon blue', 'icon_wrap' => 'stat-card-top', 'icon' => 'qa'],
                ['label' => 'Unanswered', 'value' => number_format($unansweredCount), 'sub' => 'Needs expert response', 'icon_class' => 'qa-stat-icon amber', 'icon_wrap' => 'stat-card-top', 'icon' => 'clock'],
                ['label' => 'Pending Review / Approval', 'value' => number_format($pendingCount), 'sub' => 'Awaiting moderation', 'icon_class' => 'qa-stat-icon slate', 'icon_wrap' => 'stat-card-top', 'icon' => 'pending-review-approval'],
                ['label' => 'Posted Anonymously', 'value' => $anonymousPercent.'%', 'sub' => 'Identity visible to admins', 'icon_class' => 'qa-stat-icon slate', 'icon_wrap' => 'stat-card-top', 'icon' => 'posted-anonymously'],
            ],
            'qaTabBar' => [
                'bar_class' => 'tab-bar qa-tab-bar',
                'tabs' => [
                    $this->listingTab('All Questions', 'all', $totalQuestions, $activeTab, $filters),
                    $this->listingTab('Unanswered', 'open', $unansweredCount, $activeTab, $filters),
                    $this->listingTab('Pending Approval', 'pending', $pendingCount, $activeTab, $filters),
                ],
            ],
        ];
    }

    private function listingTab(string $label, string $tab, int $count, string $activeTab, array $filters): array
    {
        return [
            'class' => 'tab-btn qa-tab',
            'count_class' => 'tab-count qa-tab-count',
            'label' => $label,
            'count' => $count,
            'active' => $tab === $activeTab,
            'href' => route('admin.dashboard.qa.index', $this->tabFilters($filters, $tab)),
            'attributes' => [
                'data-tab' => $tab,
            ],
        ];
    }

    private function tabFilters(array $filters, string $tab): array
    {
        $filters['tab'] = $tab;

        return array_filter($filters, fn ($value): bool => filled($value));
    }

    private function listingRows($questions)
    {
        return $questions->map(fn (array $question): array => [
            ...$question,
            'topic' => $this->questionTopic($question),
            'ui_status' => $this->questionUiStatus($question),
            'author_type' => $this->questionAuthorType($question),
            'search_text' => $this->questionSearchText($question),
            'posted_at_label' => $this->dateLabel($question['created_at'] ?? null),
            'display_author' => ($question['is_anonymous'] ?? false) ? 'Anonymous' : ($question['author'] ?? 'Unknown'),
            'display_role' => ($question['is_anonymous'] ?? false) ? 'Identity visible to admin' : ($question['author_role'] ?? $question['author_email'] ?? 'Verified Professional'),
            'author_initials' => $this->initials((string) ($question['author'] ?? 'QA')),
        ]);
    }

    private function questionTopic(array $question): string
    {
        $domains = $question['domains'] ?: ($question['theme'] ?? 'General');
        $topic = trim(explode(',', (string) $domains)[0] ?? 'General');

        return $topic !== '' ? $topic : 'General';
    }

    private function questionUiStatus(array $question): string
    {
        if (($question['status'] ?? null) === 'published') {
            return (int) ($question['answer_count'] ?? 0) > 0 ? 'answered' : 'open';
        }

        return $question['status'] ?? 'open';
    }

    private function questionAuthorType(array $question): string
    {
        if ($question['is_anonymous'] ?? false) {
            return 'anonymous';
        }

        if (! empty($question['on_behalf_of_partner_id'])) {
            return 'partner';
        }

        if (! empty($question['posted_by_admin_id'])) {
            return 'admin';
        }

        return 'verified';
    }

    private function questionSearchText(array $question): string
    {
        return Str::lower(trim(implode(' ', [
            $question['title'] ?? '',
            $question['body'] ?? '',
            $question['author'] ?? '',
            $question['plant'] ?? '',
            $question['domains'] ?? '',
            $question['theme'] ?? '',
        ])));
    }

    private function dateLabel(mixed $value): string
    {
        return $value ? Carbon::parse($value)->format('d M Y, H:i') : '-';
    }

    private function initials(string $name): string
    {
        $initials = collect(explode(' ', trim($name)))
            ->filter()
            ->map(fn (string $part): string => Str::substr($part, 0, 1))
            ->take(2)
            ->implode('');

        return $initials !== '' ? $initials : 'QA';
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

        return $stats;
    }

    private function questionsData(array $filters)
    {
        if (! Schema::hasTable('questions')) {
            return collect();
        }

        $questions = DB::table('questions')
            ->leftJoin('users', 'users.id', '=', 'questions.user_id')
            ->leftJoin('engineer_profiles', 'engineer_profiles.user_id', '=', 'questions.user_id')
            ->leftJoin('plant_types', 'plant_types.id', '=', 'questions.plant_type_id')
            ->leftJoin('weekly_themes', 'weekly_themes.id', '=', 'questions.weekly_theme_id')
            ->select('questions.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'users.status as user_status', 'engineer_profiles.position as engineer_position', 'engineer_profiles.current_company as engineer_company', 'engineer_profiles.photo_media_id as engineer_photo_media_id', 'plant_types.name as plant_name', 'weekly_themes.title as theme_title')
            ->when($this->filter($filters, 'tab') === 'pending', fn ($query) => $query->where('questions.status', 'pending'))
            ->when(in_array($this->filter($filters, 'tab'), ['open', 'unanswered'], true), fn ($query) => $query->whereNotExists(fn ($subQuery) => $subQuery->selectRaw('1')->from('answers')->whereColumn('answers.question_id', 'questions.id')))
            ->when($this->filter($filters, 'status') && $this->filter($filters, 'status') !== 'all', fn ($query) => $query->where('questions.status', $this->filter($filters, 'status')))
            ->when($this->integer($filters, 'plant_type_id'), fn ($query, $plantTypeId) => $query->where('questions.plant_type_id', $plantTypeId))
            ->when($this->integer($filters, 'weekly_theme_id'), fn ($query, $weeklyThemeId) => $query->where('questions.weekly_theme_id', $weeklyThemeId))
            ->when($this->filter($filters, 'author_type') && $this->filter($filters, 'author_type') !== 'all', function ($query) use ($filters): void {
                match ($this->filter($filters, 'author_type')) {
                    'anonymous' => $query->where('questions.is_anonymous', true),
                    'admin' => $query->whereNotNull('questions.posted_by_admin_id'),
                    'partner' => $query->whereNotNull('questions.on_behalf_of_partner_id'),
                    'verified' => $query->where('questions.is_anonymous', false)
                        ->whereNull('questions.posted_by_admin_id')
                        ->whereNull('questions.on_behalf_of_partner_id'),
                    default => null,
                };
            })
            ->when($this->filled($filters, 'keyword'), function ($query) use ($filters): void {
                $keyword = '%'.$this->trimmed($filters, 'keyword').'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('questions.title', 'like', $keyword)
                        ->orWhere('questions.body', 'like', $keyword)
                        ->orWhere('users.username', 'like', $keyword)
                        ->orWhere('users.first_name', 'like', $keyword)
                        ->orWhere('users.last_name', 'like', $keyword);
                });
            })
            ->when($this->filled($filters, 'date_from'), fn ($query) => $query->whereDate('questions.created_at', '>=', $this->date($filters, 'date_from')))
            ->when($this->filled($filters, 'date_to'), fn ($query) => $query->whereDate('questions.created_at', '<=', $this->date($filters, 'date_to')))
            ->latest('questions.created_at')
            ->limit(50)
            ->get()
            ->map(fn ($question): array => [
                'id' => $question->id,
                'title' => $question->title,
                'plant' => $question->plant_name ?: 'General',
                'theme' => $question->theme_title ?: 'Open discussion',
                'weekly_theme_id' => $question->weekly_theme_id,
                'plant_type_id' => $question->plant_type_id,
                'posted_by_admin_id' => $question->posted_by_admin_id ? (int) $question->posted_by_admin_id : null,
                'on_behalf_of_partner_id' => $question->on_behalf_of_partner_id ? (int) $question->on_behalf_of_partner_id : null,
                'status' => $question->status,
                'status_color' => $this->statusColor($question->status),
                'status_label' => $this->statusLabel($question->status),
                'author' => $this->userDisplayName($question, $question->user_id),
                'author_id' => $question->user_id ? (int) $question->user_id : null,
                'author_email' => $question->user_email ?: 'No email recorded',
                'author_profile_photo_url' => $this->mediaUrl((int) ($question->engineer_photo_media_id ?? 0)),
                'author_role' => $this->authorRole($question),
                'is_anonymous' => (bool) $question->is_anonymous,
                'domains' => $this->domains($question->id),
                'answer_count' => $this->answerCount((int) $question->id),
                'warning_count' => $this->warningCountForQuestion((int) $question->id),
                'attachment_count' => $this->attachmentCount($question->attachment_media_ids),
                'attachment_media_ids' => $this->mediaIds($question->attachment_media_ids),
                'confirmed_warning_count' => $this->confirmedWarningCountForUser((int) $question->user_id),
                'is_frozen' => ($question->user_status ?? null) === 'frozen',
                'views' => $this->viewCountForQuestion((int) $question->id),
                'body' => Str::limit($question->body, 220),
                'created_at' => $question->created_at,
            ]);

        return $questions;
    }

    private function authorRole(object $question): string
    {
        $role = $question->engineer_position
            ?: ($question->engineer_company ? 'Engineer at '.$question->engineer_company : 'Engineer');

        return $question->is_anonymous ? $role.' - public anonymous' : $role;
    }

    private function qaTabCounts(): array
    {
        if (! Schema::hasTable('questions')) {
            return ['all' => 0, 'open' => 0, 'pending' => 0];
        }

        $openCount = Schema::hasTable('answers')
            ? DB::table('questions')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('answers')->whereColumn('answers.question_id', 'questions.id'))
                ->count()
            : DB::table('questions')->count();

        return [
            'all' => DB::table('questions')->count(),
            'open' => $openCount,
            'pending' => DB::table('questions')->where('status', 'pending')->count(),
        ];
    }

    private function answersData(array $filters)
    {
        if (! Schema::hasTable('answers')) {
            return collect($this->demoAnswers());
        }

        $hasAnswers = DB::table('answers')->exists();

        $answers = DB::table('answers')
            ->leftJoin('questions', 'questions.id', '=', 'answers.question_id')
            ->leftJoin('users', 'users.id', '=', 'answers.user_id')
            ->select('answers.*', 'questions.title as question_title', 'questions.status as question_status', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'users.status as user_status')
            ->when($this->integer($filters, 'question_id'), fn ($query, $questionId) => $query->where('answers.question_id', $questionId))
            ->when($this->has($filters, 'is_admin_featured'), fn ($query) => $query->where('answers.is_admin_featured', $this->boolean($filters, 'is_admin_featured')))
            ->latest('answers.created_at')
            ->limit(50)
            ->get()
            ->map(fn ($answer): array => [
                'id' => $answer->id,
                'body' => Str::limit($answer->body, 120),
                'question' => $answer->question_title ?: 'Question #'.$answer->question_id,
                'status' => $answer->question_status,
                'is_anonymous' => (bool) $answer->is_anonymous,
                'attachment_media_ids' => $this->mediaIds($answer->attachment_media_ids),
                'attachment_count' => $this->attachmentCount($answer->attachment_media_ids),
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

    public function questionDetailData(int $questionId): array
    {
        if (Schema::hasTable('questions')) {
            $question = DB::table('questions')
                ->leftJoin('users', 'users.id', '=', 'questions.user_id')
                ->leftJoin('engineer_profiles', 'engineer_profiles.user_id', '=', 'questions.user_id')
                ->leftJoin('plant_types', 'plant_types.id', '=', 'questions.plant_type_id')
                ->leftJoin('weekly_themes', 'weekly_themes.id', '=', 'questions.weekly_theme_id')
                ->select('questions.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'users.status as user_status', 'engineer_profiles.position as engineer_position', 'engineer_profiles.current_company as engineer_company', 'engineer_profiles.photo_media_id as engineer_photo_media_id', 'plant_types.name as plant_name', 'weekly_themes.title as theme_title')
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
                    'status_color' => $this->statusColor($question->status),
                    'status_label' => $this->statusLabel($question->status),
                    'author' => $this->userDisplayName($question, $question->user_id),
                    'author_id' => $question->user_id ? (int) $question->user_id : null,
                    'author_profile_photo_url' => $this->mediaUrl((int) ($question->engineer_photo_media_id ?? 0)),
                    'author_role' => $this->authorRole($question),
                    'author_email' => $question->user_email ?: 'No email recorded',
                    'author_meta' => $question->is_anonymous ? 'Public anonymous; identity visible to admin' : 'Verified engineer account',
                    'is_anonymous' => (bool) $question->is_anonymous,
                    'domains' => $this->domains($question->id),
                    'answer_count' => $this->answerCount((int) $question->id),
                    'warning_count' => $this->warningCountForQuestion((int) $question->id),
                    'attachment_count' => $this->attachmentCount($question->attachment_media_ids),
                    'attachment_media_ids' => $this->mediaIds($question->attachment_media_ids),
                    'confirmed_warning_count' => $this->confirmedWarningCountForUser((int) $question->user_id),
                    'is_frozen' => ($question->user_status ?? null) === 'frozen',
                    'attachments' => $this->attachments($question->attachment_media_ids),
                    'views' => $this->viewCountForQuestion((int) $question->id),
                    'created_at' => $question->created_at,
                ];
            }
        }

        abort(404);
    }

    public function questionAnswersData(int $questionId)
    {
        if (Schema::hasTable('answers')) {
            $answers = DB::table('answers')
                ->leftJoin('users', 'users.id', '=', 'answers.user_id')
                ->leftJoin('questions', 'questions.id', '=', 'answers.question_id')
                ->select('answers.*', 'questions.status as question_status', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'users.status as user_status')
                ->where('answers.question_id', $questionId)
                ->latest('answers.created_at')
                ->get()
                ->map(fn ($answer): array => [
                    'id' => $answer->id,
                    'body' => $answer->body,
                    'status' => $answer->question_status,
                    'is_anonymous' => (bool) $answer->is_anonymous,
                    'attachment_media_ids' => $this->mediaIds($answer->attachment_media_ids),
                    'attachment_count' => $this->attachmentCount($answer->attachment_media_ids),
                    'author' => $answer->is_anonymous ? 'Anonymous' : $this->userDisplayName($answer, $answer->user_id),
                    'confidence' => $answer->confidence_level ?: 'unrated',
                    'featured' => (bool) $answer->is_admin_featured,
                    'rank' => $answer->admin_rank_order ?: '-',
                    'warning' => $this->warningForAnswer((int) $answer->id),
                ]);

            if ($answers->isNotEmpty() || (Schema::hasTable('questions') && DB::table('questions')->where('id', $questionId)->exists())) {
                return $answers;
            }
        }

        return collect();
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

    private function statusLabel(string $status): string
    {
        return [
            'published' => 'Active',
            'pending' => 'Pending Approval',
            'hidden' => 'Blocked',
            'flagged' => 'Flagged',
        ][$status] ?? Str::headline($status);
    }

    private function statusColor(string $status): string
    {
        return [
            'published' => 'success',
            'pending' => 'warning',
            'flagged' => 'danger',
            'hidden' => 'secondary',
        ][$status] ?? 'light';
    }

    private function attachmentCount(mixed $attachmentMediaIds): int
    {
        return count($this->mediaIds($attachmentMediaIds));
    }

    private function attachments(mixed $attachmentMediaIds)
    {
        $ids = $this->mediaIds($attachmentMediaIds);

        if ($ids === [] || ! Schema::hasTable('media_files')) {
            return collect();
        }

        return DB::table('media_files')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get(['id', 'original_name', 'mime_type', 'size', 'file_category']);
    }

    /**
     * @return array<int, int>
     */
    private function mediaIds(mixed $attachmentMediaIds): array
    {
        if (blank($attachmentMediaIds)) {
            return [];
        }

        $ids = is_array($attachmentMediaIds) ? $attachmentMediaIds : json_decode((string) $attachmentMediaIds, true);

        return collect(is_array($ids) ? $ids : [])
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }


    private function confirmedWarningCountForUser(int $userId): int
    {
        if ($userId <= 0 || ! Schema::hasTable('qa_user_warning_summaries')) {
            return 0;
        }

        return (int) (DB::table('qa_user_warning_summaries')
            ->where('user_id', $userId)
            ->value('confirmed_warning_count') ?? 0);
    }

    private function warningCountForQuestion(int $questionId): int
    {
        if (! Schema::hasTable('qa_moderation_warnings') || ! Schema::hasTable('answers')) {
            return 0;
        }

        return DB::table('qa_moderation_warnings')
            ->where(function ($query) use ($questionId): void {
                $query->where(function ($query) use ($questionId): void {
                    $query->where('warnable_type', 'question')
                        ->where('warnable_id', $questionId);
                })->orWhere(function ($query) use ($questionId): void {
                    $query->where('warnable_type', 'answer')
                        ->whereIn('warnable_id', DB::table('answers')->where('question_id', $questionId)->select('id'));
                });
            })
            ->count();
    }

    private function viewCountForQuestion(int $questionId): int
    {
        return Schema::hasTable('question_views')
            ? DB::table('question_views')->where('question_id', $questionId)->count()
            : 0;
    }

    private function warningForAnswer(int $answerId): ?object
    {
        if (! Schema::hasTable('qa_moderation_warnings')) {
            return null;
        }

        return DB::table('qa_moderation_warnings')
            ->where('warnable_type', 'answer')
            ->where('warnable_id', $answerId)
            ->where('status', 'confirmed')
            ->latest('created_at')
            ->first();
    }

    public function warningHistoryData(int $questionId)
    {
        if (! Schema::hasTable('qa_moderation_warnings')) {
            return collect();
        }

        $answerIds = Schema::hasTable('answers')
            ? DB::table('answers')->where('question_id', $questionId)->pluck('id')->all()
            : [];

        return DB::table('qa_moderation_warnings')
            ->where(function ($query) use ($questionId, $answerIds): void {
                $query->where(function ($query) use ($questionId): void {
                    $query->where('warnable_type', 'question')
                        ->where('warnable_id', $questionId);
                });

                if ($answerIds !== []) {
                    $query->orWhere(function ($query) use ($answerIds): void {
                        $query->where('warnable_type', 'answer')
                            ->whereIn('warnable_id', $answerIds);
                    });
                }
            })
            ->latest('created_at')
            ->get()
            ->map(fn ($warning): array => [
                'date' => $warning->created_at,
                'status' => $warning->status,
                'note' => $warning->reason,
                'severity' => $warning->severity,
            ]);
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

        return $this->questionsData([])->groupBy('weekly_theme_id');
    }

    private function assignableQuestions()
    {
        if (! Schema::hasTable('questions')) {
            return collect($this->demoQuestions());
        }

        return $this->questionsData([]);
    }

    private function leaders(?string $yearMonth = null)
    {
        $yearMonth ??= now()->format('Y-m');

        if (! Schema::hasTable('monthly_leaderboard_snapshots')) {
            return collect($this->demoLeaders());
        }

        $leaders = DB::table('monthly_leaderboard_snapshots')
            ->leftJoin('users', 'users.id', '=', 'monthly_leaderboard_snapshots.user_id')
            ->select('monthly_leaderboard_snapshots.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'users.status as user_status')
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
                ->select('user_reputation.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'users.status as user_status')
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

    public function settings(): array
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

    private function ledger(array $filters)
    {
        if (! Schema::hasTable('point_transactions')) {
            return $this->filteredDemoLedger($filters);
        }

        $ledger = DB::table('point_transactions')
            ->leftJoin('users', 'users.id', '=', 'point_transactions.user_id')
            ->leftJoin('users as performers', 'performers.id', '=', 'point_transactions.performed_by')
            ->select('point_transactions.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'users.status as user_status', 'performers.username as performer_username', 'performers.first_name as performer_first_name', 'performers.last_name as performer_last_name', 'performers.email as performer_email')
            ->when($this->filter($filters, 'source_type'), fn ($query, $sourceType) => $query->where('point_transactions.source_type', $sourceType))
            ->when($this->filled($filters, 'keyword'), function ($query) use ($filters): void {
                $keyword = '%'.$this->trimmed($filters, 'keyword').'%';
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

        return $ledger->isNotEmpty() ? $ledger : $this->filteredDemoLedger($filters);
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

    private function knowledgeDomains()
    {
        if (! Schema::hasTable('knowledge_domains')) {
            return collect();
        }

        $query = DB::table('knowledge_domains')->select('id', 'name');

        if (Schema::hasColumn('knowledge_domains', 'status')) {
            $query->where('status', 'active');
        }

        if (Schema::hasColumn('knowledge_domains', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')->limit(100)->get();
    }

    private function mediaFiles()
    {
        if (! Schema::hasTable('media_files')) {
            return collect();
        }

        return DB::table('media_files')
            ->select('id', 'original_name', 'mime_type', 'file_category')
            ->where(function ($query): void {
                $query->where('upload_context', 'question_attachment')
                    ->orWhereNull('upload_context');
            })
            ->latest('id')
            ->limit(30)
            ->get();
    }

    private function partnerProfiles()
    {
        if (! Schema::hasTable('partner_profiles')) {
            return collect();
        }

        return DB::table('partner_profiles')
            ->select('id', 'company_name', 'user_id', 'contact_email')
            ->orderBy('company_name')
            ->limit(100)
            ->get();
    }

    private function reputationUsers(array $filters)
    {
        if (! Schema::hasTable('user_reputation')) {
            return $this->filteredDemoReputationUsers($filters);
        }

        $users = DB::table('user_reputation')
            ->leftJoin('users', 'users.id', '=', 'user_reputation.user_id')
            ->select('user_reputation.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'users.status as user_status')
            ->when($this->filled($filters, 'keyword'), function ($query) use ($filters): void {
                $keyword = '%'.$this->trimmed($filters, 'keyword').'%';
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

        return $users->isNotEmpty() ? $users : $this->filteredDemoReputationUsers($filters);
    }

    private function filteredDemoQuestions(array $filters)
    {
        return collect($this->demoQuestions())
            ->when($this->filter($filters, 'status'), fn ($questions, $status) => $questions->where('status', $status))
            ->when($this->integer($filters, 'plant_type_id'), fn ($questions, $plantTypeId) => $questions->where('plant_type_id', $plantTypeId))
            ->when($this->integer($filters, 'weekly_theme_id'), fn ($questions, $weeklyThemeId) => $questions->where('weekly_theme_id', $weeklyThemeId))
            ->when($this->filled($filters, 'keyword'), function ($questions) use ($filters) {
                $keyword = Str::lower((string) $this->trimmed($filters, 'keyword'));

                return $questions->filter(fn (array $question): bool => Str::contains(Str::lower(implode(' ', [
                    $question['title'],
                    $question['body'] ?? '',
                    $question['plant'],
                    $question['theme'],
                    $question['author'],
                    $question['domains'],
                ])), $keyword));
            })
            ->when($this->filled($filters, 'date_from'), fn ($questions) => $questions->filter(fn (array $question): bool => strtotime($question['created_at']) >= strtotime((string) $this->date($filters, 'date_from'))))
            ->when($this->filled($filters, 'date_to'), fn ($questions) => $questions->filter(fn (array $question): bool => strtotime($question['created_at']) <= strtotime((string) $this->date($filters, 'date_to')->endOfDay())));
    }

    private function filteredDemoLedger(array $filters)
    {
        return collect($this->demoLedger())
            ->when($this->filter($filters, 'source_type'), fn ($ledger, $sourceType) => $ledger->where('source_type', $sourceType))
            ->when($this->filled($filters, 'keyword'), function ($ledger) use ($filters) {
                $keyword = Str::lower((string) $this->trimmed($filters, 'keyword'));

                return $ledger->filter(fn (object $entry): bool => Str::contains(Str::lower(implode(' ', [
                    $entry->display_name,
                    $entry->user_email,
                    $entry->source_type,
                    $entry->reason,
                    $entry->performed_by_name,
                ])), $keyword));
            });
    }

    private function filteredDemoReputationUsers(array $filters)
    {
        return collect($this->demoReputationUsers())
            ->when($this->filled($filters, 'keyword'), function ($users) use ($filters) {
                $keyword = Str::lower((string) $this->trimmed($filters, 'keyword'));

                return $users->filter(fn (object $user): bool => Str::contains(Str::lower(implode(' ', [
                    $user->display_name,
                    $user->email,
                    $user->username,
                ])), $keyword));
            });
    }

    private function onlyFilters(array $filters): array
    {
        return collect($filters)
            ->only(['tab', 'status', 'plant_type_id', 'weekly_theme_id', 'keyword', 'date_from', 'date_to', 'question_id', 'is_admin_featured', 'year_month', 'source_type', 'flag_status', 'author_type'])
            ->all();
    }

    private function filter(array $filters, string $key, mixed $default = null): mixed
    {
        return $filters[$key] ?? $default;
    }

    private function has(array $filters, string $key): bool
    {
        return array_key_exists($key, $filters);
    }

    private function filled(array $filters, string $key): bool
    {
        return filled($filters[$key] ?? null);
    }

    private function integer(array $filters, string $key): int
    {
        return (int) ($filters[$key] ?? 0);
    }

    private function boolean(array $filters, string $key): bool
    {
        return filter_var($filters[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function trimmed(array $filters, string $key): string
    {
        return trim((string) ($filters[$key] ?? ''));
    }

    private function date(array $filters, string $key): ?Carbon
    {
        if (! $this->filled($filters, $key)) {
            return null;
        }

        return Carbon::parse($filters[$key]);
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
            ['id' => 9103, 'title' => 'Demo: exchanger fouling trend needs review', 'body' => 'Approach temperature is drifting and the team needs a quick moderation decision before public discussion continues.', 'plant' => 'Utilities and steam', 'theme' => 'Open discussion', 'weekly_theme_id' => null, 'status' => 'unactive', 'status_label' => 'Inactive', 'status_color' => 'secondary', 'author' => 'Minh Nguyen', 'author_role' => 'Community member', 'author_email' => 'minh.demo@example.test', 'author_meta' => 'Demo profile with maintenance review history', 'domains' => 'Heat exchange', 'answer_count' => 3, 'created_at' => now()->subDays(2)->format('Y-m-d H:i')],
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

    private function mediaUrl(int $mediaId): ?string
    {
        if ($mediaId <= 0 || ! Schema::hasTable('media_files')) {
            return null;
        }

        $media = DB::table('media_files')->where('id', $mediaId)->first(['disk', 'path']);
        if (! $media || ! $media->path) {
            return null;
        }

        try {
            return Storage::disk($media->disk ?: 'public')->url($media->path);
        } catch (\Throwable) {
            return null;
        }
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
