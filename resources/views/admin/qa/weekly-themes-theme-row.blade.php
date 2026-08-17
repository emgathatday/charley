@php
    $weekDate = $dateLabel($theme->week_start_date ?? null);
    $statusClass = $statusType === 'active' ? 'active' : ($statusType === 'archived' ? 'archived' : 'scheduled');
    $statusLabel = $statusType === 'active' ? 'Active' : ($statusType === 'archived' ? 'Archived' : 'Scheduled');
    $nextStatus = ($theme->status ?? 'active') === 'active' ? 'archived' : 'active';
@endphp

<div class="theme-row">
    <div class="theme-week-badge @if($statusType === 'active') theme-week-badge-active @endif">
        <div class="wk-label">Week of</div>
        <div class="wk-date">{{ $weekDate }}</div>
    </div>
    <div class="theme-body">
        <div class="theme-title-row">
            <div class="theme-title" onclick="editTheme('{{ $theme->id }}')">{{ $theme->title }}</div>
            @if($statusType === 'active')
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-tube-integrity-catalyst-performance-and"></use></svg>
            @endif
        </div>
        <div class="theme-desc">{{ $theme->description }}</div>
        <div class="theme-tag-row">
            <span class="theme-tag plant">{{ $themeTopic($theme) }}</span>
            <span class="theme-tag equip">Q&amp;A Focus</span>
            <span class="theme-meta-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-3-questions-attached"></use></svg>{{ $assignedCount }} questions attached</span>
            <span class="theme-meta-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-library-and-pfd-content-path"></use></svg>0 articles</span>
        </div>
    </div>
    <div class="theme-status-col">
        <span class="theme-status-pill {{ $statusClass }}"><svg class="icon"><use href="/assets/icons/sprite.svg#{{ $statusType === 'archived' ? 'icon-archive' : 'icon-9-verifications-exceeded-the-48h' }}"></use></svg>{{ $statusLabel }}</span>
        <div class="theme-row-actions">
            <button class="row-action-btn @if($statusType === 'active') active-pin @endif" type="button" title="Pin" onclick="togglePin(this)"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-pin"></use></svg></button>
            <button class="row-action-btn" type="button" title="Edit" onclick="editTheme('{{ $theme->id }}')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-profile-r"></use></svg></button>
            @if($statusType === 'archived')
                <form method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.status', [$theme->id, 'active']) }}">
                    @csrf
                    <button class="row-action-btn" type="submit" title="Activate"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-duplicate-as-new-theme"></use></svg></button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.status', [$theme->id, $nextStatus]) }}">
                    @csrf
                    <button class="row-action-btn danger" type="submit" title="Archive"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-archive"></use></svg></button>
                </form>
            @endif
            <button class="row-action-btn danger" type="button" title="Delete" onclick="showToast('Theme delete is UI-only until behavior is confirmed','red')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-delete"></use></svg></button>
        </div>
    </div>
</div>