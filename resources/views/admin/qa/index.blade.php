@extends('layouts.rebuild-dashboard')

@section('title', 'Q&A Management')

@section('content')
    @include('templates.components.alert-session')

    <div class="page-head">
        <div>
            <h1>Q&amp;A Management</h1>
            <p>Moderate technical questions, review pending content, and seed cold-start discussions</p>
        </div>
        <div class="header-actions">
            <button class="btn-ghost" type="button" onclick="showToast('Export is pending a confirmed backend workflow','blue')">
                <x-admin.icon name="download-pdf" />
                <span>Export</span>
            </button>
            <a class="btn-primary" href="{{ route('admin.dashboard.qa.questions.create') }}">
                <x-admin.icon name="plus" />
                Add Seed Question
            </a>
        </div>
    </div>

    <x-admin.stat-cards :items="$qaStatCards" row-class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3 qa-stat-row" />

    <div class="filter-bar">
        <form id="qaFilterForm" method="GET" action="{{ route('admin.dashboard.qa.index') }}" class="search-form">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="filter-search">
                <x-admin.icon name="search-2" />
                <input type="text" name="keyword" id="qaSearch" placeholder="Search questions, authors, keywords..." value="{{ $filters['keyword'] ?? '' }}" onkeydown="if(event.key==='Enter')this.form.submit()">
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
                <option value="" @selected(blank($filters['status'] ?? null))>All Statuses</option>
                <option value="published" @selected(($filters['status'] ?? '') === 'published')>Active</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending Approval</option>
                <option value="flagged" @selected(($filters['status'] ?? '') === 'flagged')>Flagged</option>
                <option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Blocked</option>
            </select>
            <select class="filter-select" name="author_type" id="fAuthor" onchange="this.form.submit()">
                <option value="" @selected(blank($filters['author_type'] ?? null))>All Author Types</option>
                <option value="verified" @selected(($filters['author_type'] ?? '') === 'verified')>Verified Professional</option>
                <option value="anonymous" @selected(($filters['author_type'] ?? '') === 'anonymous')>Anonymous</option>
                <option value="partner" @selected(($filters['author_type'] ?? '') === 'partner')>Partner</option>
                <option value="admin" @selected(($filters['author_type'] ?? '') === 'admin')>Admin</option>
            </select>
            <button class="btn-primary qa-filter-btn" type="submit"><x-admin.icon name="filter" />Filter</button>
            <a class="filter-reset" href="{{ route('admin.dashboard.qa.index') }}"><x-admin.icon name="mark-review" />Reset filters</a>
        </form>
    </div>

    <x-admin.tab-bar :items="$qaTabBar" />

    <div class="bulk-bar" id="bulkBar">
        <div class="bulk-bar-text"><strong id="bulkCount">0</strong> question(s) selected</div>
        <div class="bulk-actions">
            <button class="bulk-btn" type="button" onclick="showToast('Bulk approve waits for a confirmed backend contract','green')"><x-admin.icon name="check-2" />Approve &amp; Publish</button>
            <button class="bulk-btn" type="button" onclick="showToast('Bulk review waits for a confirmed backend contract','blue')"><x-admin.icon name="pending-review-approval" />Mark Reviewed</button>
            <button class="bulk-btn danger" type="button" onclick="showToast('Delete is UI-only until behavior is confirmed','red')"><x-admin.icon name="trash" />Delete</button>
        </div>
    </div>

    <div class="table-wrap qa-table-wrap {{ $activeTab === 'pending' ? 'd-none' : '' }}" id="publishedTableWrap"><div class="table-scroll">
        <div class="table-header"><div class="table-title-block"><div class="table-title">All Questions</div><div class="table-meta">Showing {{ $publishedQuestions->count() }} of {{ number_format($totalQuestions) }} questions</div></div></div>
        <table class="qa-table">
            <thead><tr><th class="qa-check-col"><input type="checkbox" class="qa-row-checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th><th>Question</th><th>Author</th><th class="qa-metric-col">Answers</th><th class="qa-metric-col">Views</th><th>Posted</th><th class="qa-actions-col">Actions</th></tr></thead>
            <tbody id="qaTableBody">
            @forelse ($publishedQuestions as $question)
                <tr data-plant="{{ $question['plant_type_id'] }}" data-topic="{{ $question['weekly_theme_id'] ?? $question['topic'] }}" data-status="{{ $question['ui_status'] }}" data-publish="{{ $question['status'] }}" data-authortype="{{ $question['author_type'] }}" data-search="{{ $question['search_text'] }}">
                    <td><input type="checkbox" class="qa-row-checkbox" onclick="onRowCheck()"></td>
                    <td>
                        <a class="qa-q-title" href="{{ route('admin.dashboard.qa.questions.show', $question['id']) }}">{{ $question['title'] }}</a>
                        <div class="qa-q-excerpt">{{ $question['body'] }}</div>
                        <div class="qa-badge-row">
                            <span class="qa-mini-badge plant">{{ $question['plant'] }}</span><span class="qa-mini-badge topic">{{ $question['topic'] }}</span><span class="qa-mini-badge">{{ $question['status_label'] }}</span>
                            @if((int) $question['attachment_count'] > 0)<span class="qa-mini-badge attach"><x-admin.icon name="files" />{{ $question['attachment_count'] }} files</span>@endif
                            @if((int) $question['warning_count'] > 0)<span class="qa-mini-badge">{{ $question['warning_count'] }} warnings</span>@endif
                        </div>
                    </td>
                    <td>
                        <div class="qa-author">
                            <div class="qa-author-avatar {{ $question['is_anonymous'] ? 'anon' : '' }}">@if($question['is_anonymous'])<x-admin.icon name="anonymous-identity-visible" />@else{{ $question['author_initials'] }}@endif</div>
                            <div><div class="qa-author-name">{{ $question['display_author'] }}</div><div class="qa-author-role">{{ $question['display_role'] }}</div></div>
                        </div>
                    </td>
                    <td class="qa-stat-col"><div class="qa-stat-num">{{ $question['answer_count'] }}</div></td>
                    <td class="qa-stat-col"><div class="qa-stat-num">{{ $question['views'] }}</div></td>
                    <td class="qa-date">{{ $question['posted_at_label'] }}</td>
                    <td><div class="row-actions"><a class="row-action-btn" title="View" href="{{ route('admin.dashboard.qa.questions.show', $question['id']) }}"><x-admin.icon name="eye" /></a><button class="row-action-btn danger" type="button" title="Delete" onclick="showToast('Delete is UI-only until behavior is confirmed','red')"><x-admin.icon name="trash" /></button></div></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state show"><span>No questions match the selected filters.</span></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="empty-state" id="emptyState"><x-admin.icon name="search-2" /><div class="empty-state-title">No questions match these filters</div><div class="empty-state-sub">Try a different plant type, topic, or reset filters to see all questions.</div></div>
    <div class="pagination"><div class="page-info">Showing <strong id="visibleCount">{{ $visiblePublished }}</strong> of {{ number_format($totalQuestions) }} questions</div><div class="qa-pagination-btns"><button class="page-btn" type="button" disabled>&lt;</button><button class="page-btn active" type="button">1</button><button class="page-btn" type="button" disabled>&gt;</button></div></div>
    </div>

    <div class="table-wrap qa-table-wrap {{ $activeTab === 'pending' ? '' : 'd-none' }}" id="pendingTableWrap"><div class="table-scroll">
        <table class="qa-table">
            <thead><tr><th class="qa-check-col"><input type="checkbox" class="qa-row-checkbox" id="selectAllPending" onclick="toggleSelectAllPending(this)"></th><th>Question</th><th>Author</th><th>Posted</th><th class="qa-actions-col wide">Actions</th></tr></thead>
            <tbody id="qaPendingTableBody">
            @forelse ($pendingQuestions as $question)
                <tr data-plant="{{ $question['plant_type_id'] }}" data-topic="{{ $question['weekly_theme_id'] ?? $question['topic'] }}" data-authortype="{{ $question['author_type'] }}" data-search="{{ $question['search_text'] }}" id="pendingRow{{ $question['id'] }}">
                    <td><input type="checkbox" class="qa-row-checkbox" onclick="onRowCheck()"></td>
                    <td><a class="qa-q-title" href="{{ route('admin.dashboard.qa.questions.show', $question['id']) }}">{{ $question['title'] }}</a><div class="qa-q-excerpt">{{ $question['body'] }}</div><div class="qa-badge-row"><span class="qa-mini-badge plant">{{ $question['plant'] }}</span><span class="qa-mini-badge topic">{{ $question['topic'] }}</span></div></td>
                    <td><div class="qa-author"><div class="qa-author-avatar {{ $question['is_anonymous'] ? 'anon' : '' }}">@if($question['is_anonymous'])<x-admin.icon name="anonymous-identity-visible" />@else{{ $question['author_initials'] }}@endif</div><div><div class="qa-author-name">{{ $question['display_author'] }}</div><div class="qa-author-role">{{ $question['display_role'] }}</div></div></div></td>
                    <td class="qa-date">{{ $question['posted_at_label'] }}</td>
                    <td><div class="row-actions"><form method="POST" action="{{ route('admin.dashboard.qa.questions.status', [$question['id'], 'published']) }}">@csrf<button class="approve-btn" type="submit"><x-admin.icon name="check-2" />Approve</button></form><button class="row-action-btn danger" type="button" title="Delete" onclick="showToast('Delete is UI-only until behavior is confirmed','red')"><x-admin.icon name="trash" /></button></div></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty-state show"><span>No questions pending approval.</span></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="empty-state" id="emptyStatePending"><x-admin.icon name="pending-review-approval" /><div class="empty-state-title">No questions pending approval</div><div class="empty-state-sub">New submissions and flagged content awaiting review will show up here.</div></div>
    <div class="qa-pagination"><div class="qa-pagination-text"><strong id="pendingVisibleCount">{{ $visiblePending }}</strong> question(s) awaiting approval</div></div>
    </div>

    <div class="toast-container" id="toastContainer"></div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/qa-management.js') }}"></script>
@endpush
