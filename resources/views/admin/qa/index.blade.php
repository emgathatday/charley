@extends('layouts.rebuild-dashboard')

@section('title', 'Q&A Management')

@section('content')
@php
    $questions = collect($questions ?? []);
    $activeTab = $filters['tab'] ?? 'all';
    $totalQuestions = $qaTabCounts['all'] ?? $questions->count();
    $unansweredCount = $qaTabCounts['open'] ?? $questions->filter(fn ($question) => (int) ($question['answer_count'] ?? 0) === 0)->count();
    $pendingCount = $qaTabCounts['pending'] ?? $questions->filter(fn ($question) => ($question['status'] ?? '') === 'pending')->count();
    $tabFilters = [
        'all' => ['label' => 'All Questions', 'tab' => 'all', 'count' => $totalQuestions],
        'open' => ['label' => 'Unanswered', 'tab' => 'open', 'count' => $unansweredCount],
        'pending' => ['label' => 'Pending Approval', 'tab' => 'pending', 'count' => $pendingCount],
    ];
    $anonymousCount = $questions->filter(fn ($question) => (bool) ($question['is_anonymous'] ?? false))->count();
    $placeholderAvatar = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgcng9IjEwIiBmaWxsPSIjRjFGNEY4Ii8+PGNpcmNsZSBjeD0iNTAiIGN5PSIzOCIgcj0iMTgiIGZpbGw9IiNDOEQwREEiLz48cGF0aCBkPSJNNTAgNjBjLTIyIDAtMzQgMTQtMzQgMzJ2OGg2OHYtOGMwLTE4LTEyLTMyLTM0LTMyeiIgZmlsbD0iI0M4RDBEQSIvPjwvc3ZnPg==';
    $anonymousPercent = $totalQuestions > 0 ? round(($anonymousCount / $totalQuestions) * 100) : 0;
    $topicOptions = $questions->pluck('domains')->filter()->flatMap(fn ($domains) => collect(explode(',', $domains))->map(fn ($domain) => trim($domain))->filter())->unique()->values();
    $formatDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y, H:i') : '-';
    $slug = fn ($value) => Str::slug((string) $value) ?: 'general';
    $statusToUi = fn ($status, $answers = 0) => $status === 'published' && (int) $answers > 0 ? 'answered' : (($status === 'published') ? 'open' : $status);
    $authorType = fn ($question) => ($question['is_anonymous'] ?? false) ? 'anonymous' : (($question['author'] ?? '') === 'Charley Admin' ? 'admin' : 'verified');
    $qaStatCards = [
        ['label' => 'Total Questions', 'value' => number_format($totalQuestions), 'sub' => 'Across current filters', 'icon_class' => 'qa-stat-icon blue', 'icon_wrap' => 'stat-card-top', 'icon' => 'icon-qa'],
        ['label' => 'Unanswered', 'value' => number_format($unansweredCount), 'sub' => 'Needs expert response', 'icon_class' => 'qa-stat-icon amber', 'icon_wrap' => 'stat-card-top', 'icon' => 'icon-clock'],
        ['label' => 'Pending Review / Approval', 'value' => number_format($pendingCount), 'sub' => 'Awaiting moderation', 'icon_class' => 'qa-stat-icon slate', 'icon_wrap' => 'stat-card-top', 'icon' => 'icon-pending-review-approval'],
        ['label' => 'Posted Anonymously', 'value' => $anonymousPercent . '%', 'sub' => 'Identity visible to admins', 'icon_class' => 'qa-stat-icon slate', 'icon_wrap' => 'stat-card-top', 'icon' => 'icon-anonymous-identity-visible'],
    ];
@endphp

    @include('templates.components.alert-session')

    <div class="page-head">
        <div>
            <h1>Q&amp;A Management</h1>
            <p>Moderate technical questions, review pending content, and seed cold-start discussions</p>
        </div>
        <div class="header-actions">
            <button class="btn-ghost" type="button" onclick="showToast('Export is pending a confirmed backend workflow','blue')">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-download-pdf"></use></svg>
                <span>Export</span>
            </button>
            <button class="btn-primary" type="button" onclick="showToast('Seed question form is waiting for the confirmed create contract','blue')">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-plus"></use></svg>
                Add Seed Question
            </button>
        </div>
    </div>

    <x-admin.stat-cards :items="$qaStatCards" row-class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3 qa-stat-row" />

    <div class="filter-bar">
        <form id="qaFilterForm" method="GET" action="{{ route('admin.dashboard.qa.index') }}" class="search-form">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="filter-search">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-search-2"></use></svg>
                <input type="text" name="keyword" id="qaSearch" placeholder="Search questions, authors, keywords..." value="{{ $filters['keyword'] ?? '' }}">
            </div>
            <select class="filter-select" name="plant_type_id" id="fPlant" onchange="this.form.submit()">
                <option value="">All Plant Types</option>
                @foreach ($plantTypes as $plantType)
                    <option value="{{ $plantType->id }}" @selected((string) ($filters['plant_type_id'] ?? '') === (string) $plantType->id)>{{ $plantType->name }}</option>
                @endforeach
            </select>
            <select class="filter-select" name="weekly_theme_id" id="fTopic" onchange="this.form.submit()">
                <option value="">All Topics</option>
                @foreach ($weeklyThemes as $weeklyTheme)
                    <option value="{{ $weeklyTheme->id }}" @selected((string) ($filters['weekly_theme_id'] ?? '') === (string) $weeklyTheme->id)>{{ $weeklyTheme->title }}</option>
                @endforeach
            </select>
            <select class="filter-select" name="status" id="fStatus" onchange="this.form.submit()">
                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All Statuses</option>
                <option value="published" @selected(($filters['status'] ?? 'all') === 'published')>Active</option>
                <option value="pending" @selected(($filters['status'] ?? 'all') === 'pending')>Pending Approval</option>
                <option value="flagged" @selected(($filters['status'] ?? 'all') === 'flagged')>Flagged</option>
                <option value="hidden" @selected(($filters['status'] ?? 'all') === 'hidden')>Blocked</option>
            </select>
            <button class="btn-primary qa-filter-btn" type="submit">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter"></use></svg>
                Filter
            </button>
            <a class="filter-reset" href="{{ route('admin.dashboard.qa.index') }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-mark-for-review-svg-viewbox-0"></use></svg>
                Reset filters
            </a>
        </form>
    </div>
    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            @php
                $qaTabBar = [
                    'bar_class' => 'tab-bar qa-tab-bar',
                    'tabs' => collect($tabFilters)->map(fn ($tab) => [
                        'class' => 'tab-btn qa-tab',
                        'count_class' => 'tab-count qa-tab-count',
                        'label' => $tab['label'],
                        'count' => $tab['count'],
                        'active' => $tab['tab'] === $activeTab,
                        'href' => route('admin.dashboard.qa.index', ['tab' => $tab['tab'], 'keyword' => $filters['keyword'] ?? '', 'plant_type_id' => $filters['plant_type_id'] ?? '', 'weekly_theme_id' => $filters['weekly_theme_id'] ?? '', 'status' => $filters['status'] ?? 'all']),
                    ])->all(),
                ];
            @endphp
            <x-admin.tab-bar :items="$qaTabBar" />
        </div>
    </div>
    <div class="bulk-bar" id="bulkBar">
        <div class="bulk-bar-text"><strong id="bulkCount">0</strong> question(s) selected</div>
        <div class="bulk-actions">
            <button class="bulk-btn" type="button" onclick="showToast('Bulk approve waits for a confirmed backend contract','green')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-check-2"></use></svg>Approve &amp; Publish</button>
            <button class="bulk-btn" type="button" onclick="showToast('Bulk review waits for a confirmed backend contract','blue')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-pending-review-approval"></use></svg>Mark Reviewed</button>
            <button class="bulk-btn danger" type="button" onclick="showToast('Delete is UI-only until behavior is confirmed','red')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-delete"></use></svg>Delete</button>
        </div>
    </div>

    <div class="table-wrap qa-table-wrap" id="publishedTableWrap">
        <div class="table-scroll">
            <div class="table-header">
                <div class="table-title-block">
                    <div class="table-title">All Questions</div>
                    <div class="table-meta">Showing {{ $questions->count() }} of {{ number_format($totalQuestions) }} questions</div>
                </div>
            </div>
            <table class="qa-table">
                <thead>
                    <tr>
                        <th class="qa-check-col"><input type="checkbox" class="qa-row-checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                        <th>Question</th>
                        <th>Author</th>
                        <th class="qa-metric-col">Answers</th>
                        <th class="qa-metric-col">Views</th>
                        <th>Posted</th>
                        <th class="qa-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody id="qaTableBody">
                    @forelse ($questions as $question)
                        @php
                            $domains = $question['domains'] ?: $question['theme'];
                            $topic = trim(explode(',', $domains)[0] ?? 'General');
                            $uiStatus = $statusToUi($question['status'], $question['answer_count'] ?? 0);
                            $profilePhotoUrl = $question['author_profile_photo_url'] ?? null;
                            $authorEmail = $question['author_email'] ?? 'No email recorded';
                        @endphp
                        <tr data-plant="{{ $slug($question['plant']) }}" data-topic="{{ $slug($topic) }}" data-status="{{ $uiStatus }}" data-publish="{{ $question['status'] }}" data-answer-count="{{ (int) ($question['answer_count'] ?? 0) }}" data-authortype="{{ $authorType($question) }}" data-search="{{ Str::lower($question['title'].' '.$question['body'].' '.$question['author'].' '.$question['plant'].' '.$domains) }}">
                            <td><input type="checkbox" class="qa-row-checkbox" onclick="onRowCheck()"></td>
                            <td>
                                <a class="qa-q-title" href="{{ route('admin.dashboard.qa.questions.show', $question['id']) }}">{{ $question['title'] }}</a>
                                <div class="qa-q-excerpt">{{ $question['body'] }}</div>
                                <div class="qa-badge-row"><span class="qa-mini-badge plant">{{ $question['plant'] }}</span><span class="qa-mini-badge topic">{{ $topic }}</span><span class="qa-mini-badge">{{ $question['status_label'] ?? Str::headline($question['status']) }}</span>@if((int) ($question['attachment_count'] ?? 0) > 0)<span class="qa-mini-badge">{{ $question['attachment_count'] }} attachments</span>@endif @if((int) ($question['warning_count'] ?? 0) > 0)<span class="qa-mini-badge">{{ $question['warning_count'] }} warnings</span>@endif</div>
                            </td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar"><img class="user-avatar-img" src="{{ $profilePhotoUrl ?: $placeholderAvatar }}" alt="{{ $question['author'] }}" style="width: 100%;height: 100%;object-fit: cover;"></div>
                                    <div>@if(! empty($question['author_id']))<a class="user-name" href="{{ route('admin.dashboard.iam.users.show', $question['author_id']) }}">{{ $question['author'] }}</a>@else<div class="user-name">{{ $question['author'] }}</div>@endif<div class="user-email">{{ $authorEmail }}</div></div>
                                </div>
                            </td>
                            <td class="qa-stat-col"><div class="qa-stat-num">{{ $question['answer_count'] ?? 0 }}</div></td>
                            <td class="qa-stat-col"><div class="qa-stat-num">{{ $question['views'] ?? 0 }}</div></td>
                            <td class="qa-date">{{ $formatDate($question['created_at'] ?? null) }}</td>
                            <td>
                                <div class="row-actions">
                                    <a class="row-action-btn" title="View" href="{{ route('admin.dashboard.qa.questions.show', $question['id']) }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-input-type-checkbox-id"></use></svg></a>
                                    <button class="row-action-btn danger" type="button" title="Delete" onclick="showToast('Delete is UI-only until behavior is confirmed','red')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-delete"></use></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state show"><span>No questions match the selected filters.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="empty-state" id="emptyState"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-search-2"></use></svg><div class="empty-state-title">No questions match these filters</div><div class="empty-state-sub">Try a different plant type, topic, or reset filters to see all questions.</div></div>
        <div class="pagination"><div class="page-info">Showing <strong id="visibleCount">{{ $questions->count() }}</strong> of {{ number_format($totalQuestions) }} questions</div><div class="qa-pagination-btns"><button class="page-btn" type="button" disabled>&lt;</button><button class="page-btn active" type="button">1</button><button class="page-btn" type="button" disabled>&gt;</button></div></div>
    </div>

    <div class="toast-container" id="toastContainer"></div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/qa-management.js') }}"></script>
@endpush