@extends('layouts.rebuild-dashboard')

@section('title', 'Weekly Theme Management')

@section('content')
@php
    $themes = collect($themes ?? []);
    $themeAssignments = collect($themeAssignments ?? []);
    $assignableQuestions = collect($assignableQuestions ?? []);
    $today = now()->startOfDay();
    $dateLabel = function ($value, string $format = 'd M') {
        return $value ? \Illuminate\Support\Carbon::parse($value)->format($format) : '-';
    };
    $activeThemes = $themes->filter(fn ($theme) => ($theme->status ?? 'active') === 'active' && $theme->week_start_date && $theme->week_end_date && \Illuminate\Support\Carbon::parse($theme->week_start_date)->startOfDay()->lte($today) && \Illuminate\Support\Carbon::parse($theme->week_end_date)->endOfDay()->gte($today))->values();
    $upcomingThemes = $themes->filter(fn ($theme) => ($theme->status ?? 'active') === 'active' && $theme->week_start_date && \Illuminate\Support\Carbon::parse($theme->week_start_date)->startOfDay()->gt($today))->values();
    $archivedThemes = $themes->filter(fn ($theme) => ($theme->status ?? '') === 'archived')->values();
    $fallbackActive = $activeThemes->first() ?: $themes->first();
    $assignedCount = function ($theme) use ($themeAssignments) {
        return $themeAssignments->get($theme->id, collect())->count() ?: ($theme->assigned_questions_count ?? 0);
    };
    $themeTopic = fn ($theme) => trim(explode(' ', (string) $theme->title)[0] ?? 'Theme') ?: 'Theme';
@endphp

    @include('templates.components.alert-session')

    <div class="page-head">
        <div>
            <div class="page-title weekly-theme-title">Weekly Theme Management</div>
            <div class="page-subtitle">Set the featured technical topic that anchors the Q&amp;A section each week</div>
        </div>
        <div class="header-actions">
            <button class="btn-primary" type="button" onclick="openThemeModal()">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-plus"></use></svg>
                Create New Theme
            </button>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3 weekly-theme-stat-row">
        @foreach ([
            ['label' => 'Total Themes Created', 'value' => $themes->count(), 'tone' => 'primary', 'icon' => 'icon-5-jul-2026-14-32-circle'],
            ['label' => 'Active This Week', 'value' => $activeThemes->count(), 'tone' => 'success', 'icon' => 'icon-clock'],
            ['label' => 'Scheduled Ahead', 'value' => $upcomingThemes->count(), 'tone' => 'warning', 'icon' => 'icon-monthly-expert-recognition-svg-viewbox-0'],
            ['label' => 'Archived', 'value' => $archivedThemes->count(), 'tone' => 'muted', 'icon' => 'icon-archive'],
        ] as $stat)
            <div class="col">
                <div class="stat-card">
                    <div class="stat-card-top"><div class="stat-icon weekly-theme-stat-icon {{ $stat['tone'] }}"><svg class="icon"><use href="/assets/icons/sprite.svg#{{ $stat['icon'] }}"></use></svg></div></div>
                    <div class="stat-value">{{ number_format($stat['value']) }}</div>
                    <div class="stat-label">{{ $stat['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($fallbackActive)
        <div class="hero-theme">
            <div class="hero-live-tag"><span class="hero-live-dot"></span>{{ ($fallbackActive->status ?? 'active') === 'active' ? 'Live now' : 'Latest theme' }} - Week of {{ $dateLabel($fallbackActive->week_start_date ?? null, 'd M Y') }}</div>
            <div class="hero-title">{{ $fallbackActive->title }}</div>
            <div class="hero-desc">{{ $fallbackActive->description }}</div>
            <div class="hero-badges">
                <span class="hero-badge">{{ $themeTopic($fallbackActive) }}</span>
                <span class="hero-badge">Q&amp;A Focus</span>
                <span class="hero-badge">Technical Discussion</span>
            </div>
            <div class="hero-stats-row">
                <div class="hero-stat"><div class="hero-stat-num">{{ $assignedCount($fallbackActive) }}</div><div class="hero-stat-label">Featured Questions</div></div>
                <div class="hero-stat"><div class="hero-stat-num">0</div><div class="hero-stat-label">Library Articles Attached</div></div>
                <div class="hero-stat"><div class="hero-stat-num">0</div><div class="hero-stat-label">Partner Contributions</div></div>
                <div class="hero-stat"><div class="hero-stat-num">0</div><div class="hero-stat-label">Views So Far</div></div>
            </div>
            <div class="hero-actions">
                <button class="hero-btn primary" type="button" onclick="editTheme('{{ $fallbackActive->id }}')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-3"></use></svg>Edit This Week's Theme</button>
                <button class="hero-btn" type="button" onclick="showToast('Announcement drafting waits for a confirmed backend workflow','blue')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-announcements"></use></svg>Post Announcement</button>
                <form method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.status', [$fallbackActive->id, 'archived']) }}">
                    @csrf
                    <button class="hero-btn" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-archive"></use></svg>Archive Now</button>
                </form>
            </div>
        </div>
    @endif

    <div class="tab-bar qa-tab-bar weekly-theme-tab-bar">
        <button type="button" class="tab-btn qa-tab active" data-tab="upcoming" onclick="switchThemeTab(this)">Upcoming <span class="tab-count qa-tab-count">{{ $upcomingThemes->count() }}</span></button>
        <button type="button" class="tab-btn qa-tab" data-tab="active" onclick="switchThemeTab(this)">Active <span class="tab-count qa-tab-count">{{ $activeThemes->count() }}</span></button>
        <button type="button" class="tab-btn qa-tab" data-tab="archived" onclick="switchThemeTab(this)">Archived <span class="tab-count qa-tab-count">{{ $archivedThemes->count() }}</span></button>
    </div>

    <div class="theme-list-wrap" id="upcomingList">
        @forelse ($upcomingThemes as $theme)
            @include('admin.qa.weekly-themes-theme-row', ['theme' => $theme, 'statusType' => 'scheduled', 'assignedCount' => $assignedCount($theme), 'dateLabel' => $dateLabel, 'themeTopic' => $themeTopic])
        @empty
            <div class="empty-state show"><div class="empty-state-title">No upcoming themes scheduled</div><div class="empty-state-sub">Create a new weekly theme to plan the next Q&amp;A spotlight.</div></div>
        @endforelse
    </div>

    <div class="theme-list-wrap d-none" id="activeList">
        @forelse ($activeThemes as $theme)
            @include('admin.qa.weekly-themes-theme-row', ['theme' => $theme, 'statusType' => 'active', 'assignedCount' => $assignedCount($theme), 'dateLabel' => $dateLabel, 'themeTopic' => $themeTopic])
        @empty
            <div class="empty-state show"><div class="empty-state-title">No active theme this week</div><div class="empty-state-sub">Activate a scheduled theme when the weekly campaign is ready.</div></div>
        @endforelse
    </div>

    <div class="theme-list-wrap d-none" id="archivedList">
        @forelse ($archivedThemes as $theme)
            @include('admin.qa.weekly-themes-theme-row', ['theme' => $theme, 'statusType' => 'archived', 'assignedCount' => $assignedCount($theme), 'dateLabel' => $dateLabel, 'themeTopic' => $themeTopic])
        @empty
            <div class="empty-state show"><div class="empty-state-title">No archived themes</div><div class="empty-state-sub">Completed themes will appear here for reuse and review.</div></div>
        @endforelse
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <div class="modal-overlay" id="themeModal" onclick="if(event.target===this)closeModal('theme')">
        <form class="modal theme-modal" method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.store') }}">
            @csrf
            <div class="tm-header">
                <div class="tm-header-title" id="tmHeaderTitle">Create New Theme</div>
                <button class="tm-close" type="button" onclick="closeModal('theme')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-x"></use></svg></button>
            </div>
            <div class="tm-body">
                <div class="tm-field"><label class="tm-label" for="tmTitle">Theme Title</label><input class="tm-input" id="tmTitle" name="title" type="text" placeholder="e.g. Primary Reformer Reliability" required></div>
                <div class="tm-field"><label class="tm-label" for="tmDescription">Short Description</label><textarea class="tm-textarea" id="tmDescription" name="description" placeholder="A short summary shown on the homepage and Q&amp;A section..."></textarea></div>
                <div class="row row-cols-1 row-cols-md-3 g-3 tm-field">
                    <div class="col"><label class="tm-label" for="tmWeekStart">Week Start Date</label><input class="tm-input" id="tmWeekStart" name="week_start_date" type="date" value="{{ now()->addWeek()->startOfWeek()->toDateString() }}" required></div>
                    <div class="col"><label class="tm-label" for="tmWeekEnd">Week End Date</label><input class="tm-input" id="tmWeekEnd" name="week_end_date" type="date" value="{{ now()->addWeek()->endOfWeek()->toDateString() }}" required></div>
                    <div class="col"><label class="tm-label" for="tmStatus">Status</label><select class="tm-select" id="tmStatus" name="status"><option value="active">Active</option><option value="archived">Archived</option></select></div>
                </div>
                <div class="tm-field"><label class="tm-label">Related Plant Type</label><div class="tm-chip-picker" id="tmPlantChips">@foreach ($plantTypes as $plantType)<div class="tm-chip" data-val="{{ $plantType->name }}" onclick="toggleChip(this)">{{ $plantType->name }}</div>@endforeach</div></div>
                <div class="tm-field"><label class="tm-label">Related Equipment / Topic</label><div class="tm-chip-picker" id="tmEquipChips"><div class="tm-chip" data-val="Primary Reformer" onclick="toggleChip(this)">Primary Reformer</div><div class="tm-chip" data-val="CO2 Removal" onclick="toggleChip(this)">CO2 Removal</div><div class="tm-chip" data-val="Synthesis Loop" onclick="toggleChip(this)">Synthesis Loop</div><div class="tm-chip" data-val="Compression" onclick="toggleChip(this)">Compression</div><div class="tm-chip" data-val="Safety" onclick="toggleChip(this)">Safety</div><div class="tm-chip" data-val="Integrity" onclick="toggleChip(this)">Integrity</div></div></div>
                <div class="tm-field"><label class="tm-label">Featured Q&amp;A</label><div class="tm-select-list">@forelse ($assignableQuestions->take(5) as $question)<label class="tm-select-item"><input type="checkbox" disabled> {{ $question['title'] }}</label>@empty<label class="tm-select-item"><input type="checkbox" disabled> No assignable questions available</label>@endforelse</div><div class="tm-hint">Question assignment is display-only here until the assign/remove routes are registered.</div></div>
                <div class="tm-field"><div class="tm-toggle-row"><div><div class="tm-toggle-label">Pin this theme</div><div class="tm-toggle-sub">Pinned ordering is UI-only until a persistence contract is confirmed</div></div><label class="switch"><input type="checkbox" id="tmPinToggle" disabled><span class="slider"></span></label></div></div>
            </div>
            <div class="tm-footer"><button class="btn-ghost" type="button" onclick="closeModal('theme')">Cancel</button><button class="btn-ghost" type="button" onclick="showToast('Draft persistence is waiting for a confirmed backend workflow','blue')">Save as Draft</button><button class="btn-primary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-check-2"></use></svg><span id="tmSaveLabel">Schedule Theme</span></button></div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/weekly-theme-management.js') }}"></script>
@endpush