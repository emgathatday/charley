@extends('layouts.rebuild-dashboard')

@section('title', 'Administrator Management')

@php
    $activeTab = $filters['tab'] ?? 'all';
    $allCount = $stats['total_users'] ?? $users->total();
    $activeCount = $stats['active_members'] ?? 0;
    $moderatorCount = $stats['registered_members'] ?? 0;
    $pendingCount = $stats['pending_approvals'] ?? 0;
    $restrictedCount = ($stats['frozen_users'] ?? 0) + ($stats['suspended_users'] ?? 0);
    $tabFilters = [
        'all' => ['label' => 'All Operators', 'tab' => 'all', 'count' => $allCount],
        'active' => ['label' => 'Active', 'tab' => 'active', 'count' => $activeCount],
        'pending' => ['label' => 'Pending Review', 'tab' => 'pending', 'count' => $pendingCount],
        'restricted' => ['label' => 'Restricted', 'tab' => 'restricted', 'count' => $restrictedCount],
    ];
    $initials = function ($name) {
        return collect(explode(' ', trim((string) $name)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') ?: 'A';
    };
    $roleMeta = fn ($user) => match ($user->role) {
        'admin' => ['label' => 'Admin', 'class' => 'professional'],
        'moderator' => ['label' => 'Moderator', 'class' => 'member'],
        default => ['label' => str_replace('_', ' ', ucfirst((string) $user->role)), 'class' => 'member'],
    };
    $securityClass = fn ($user) => $user->mfa_enabled && ! $user->locked_until ? 'senior' : ((int) $user->login_attempts > 0 || $user->locked_until ? 'registered' : 'professional2');
    $statusClass = fn ($user) => in_array($user->status, ['active', 'pending', 'suspended', 'frozen'], true) ? $user->status : 'pending';
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Administrator Management</h1>
            <p>Manage internal admin and moderator accounts, review security posture, and monitor operator access.</p>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3">
        <div class="col"><div class="stat-card blue"><div class="stat-label">Total Operators</div><div class="stat-value">{{ number_format($allCount) }}</div><div class="stat-sub">Admin and moderator accounts</div><div class="stat-chip up">{{ number_format($users->count()) }} this page</div></div></div>
        <div class="col"><div class="stat-card indigo"><div class="stat-label">Active Operators</div><div class="stat-value">{{ number_format($activeCount) }}</div><div class="stat-sub">Currently active access</div><div class="stat-chip up">Active</div></div></div>
        <div class="col"><div class="stat-card amber"><div class="stat-label">Moderators</div><div class="stat-value">{{ number_format($moderatorCount) }}</div><div class="stat-sub">Scoped internal operators</div><div class="stat-chip warn">Role scoped</div></div></div>
        <div class="col"><div class="stat-card"><div class="stat-label">Restricted</div><div class="stat-value">{{ number_format($restrictedCount) }}</div><div class="stat-sub">Frozen or suspended operators</div><div class="stat-chip red">{{ number_format($stats['suspended_users'] ?? 0) }} suspended</div></div></div>
    </div>

    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            <div class="tab-bar">
                @foreach ($tabFilters as $tab)
                    <a class="tab-btn {{ $tab['tab'] === $activeTab ? 'active' : '' }}" href="{{ route('admin.dashboard.iam.users', ['tab' => $tab['tab'], 'keyword' => $filters['keyword'] ?? '', 'status' => $filters['status'] ?? 'all']) }}">
                        {{ $tab['label'] }} <span class="tab-count">{{ number_format($tab['count']) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-12 col-xl-auto d-flex justify-content-xl-end">
            <a class="btn-primary" href="{{ route('admin.dashboard.iam.users.create-admin') }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-user"></use></svg>
                Add Administrator
            </a>
        </div>
    </div>

    <div class="bulk-bar" id="bulkBar">
        <span class="bulk-count" id="bulkCount">0 operators selected</span>
        <div class="bulk-actions"><button class="bulk-btn suspend" type="button">Suspend</button><button class="bulk-btn export" type="button">Export Selected</button></div>
        <button type="button" onclick="clearSelection()">Clear</button>
    </div>

    <div class="table-wrap">
        <form id="adminFilterForm" method="GET" action="{{ route('admin.dashboard.iam.users') }}"><input type="hidden" name="tab" value="{{ $activeTab }}"></form>
        <div class="table-header">
            <div><select class="filter-select" id="bulkActionSelect"><option value="">Bulk Actions</option><option value="suspend">Suspend</option><option value="freeze">Freeze</option><option value="export">Export</option></select></div>
            <div><button class="btn-apply" type="button" onclick="applyBulkAction()">Apply</button></div>
            <div class="search-form">
                <div class="search-box"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-k-overview-a-href-admin-d"></use></svg><input form="adminFilterForm" type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Search administrators..."></div>
                <select form="adminFilterForm" class="filter-select" name="status" onchange="this.form.submit()">
                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All Statuses</option>
                    <option value="active" @selected(($filters['status'] ?? 'all') === 'active')>Active</option>
                    <option value="pending" @selected(($filters['status'] ?? 'all') === 'pending')>Pending Review</option>
                    <option value="suspended" @selected(($filters['status'] ?? 'all') === 'suspended')>Suspended</option>
                    <option value="frozen" @selected(($filters['status'] ?? 'all') === 'frozen')>Frozen</option>
                </select>
                <button form="adminFilterForm" class="btn-outline btn-filter" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter-account-acc"></use></svg>Filter</button>
            </div>
            <div class="table-title-block"><div class="table-title">Administrator List</div><div class="table-meta">Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} operators</div></div>
        </div>

        <div class="table-scroll">
            <table>
                <thead><tr><th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th><th>Operator</th><th>Account Type</th><th>Security</th><th>Last Login</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody id="userTableBody">
                    @forelse ($users as $user)
                        @php $role = $roleMeta($user); @endphp
                        <tr>
                            <td><input class="row-check" type="checkbox" onchange="handleRowCheck()"></td>
                            <td><div class="user-cell"><div class="user-avatar"><div class="avatar">{{ $initials($user->display_name) }}</div></div><div><a class="user-name" href="{{ route('admin.dashboard.iam.users.show', $user) }}">{{ $user->display_name }}</a><div class="user-email">{{ $user->email }}</div></div></div></td>
                            <td><span class="role-badge {{ $role['class'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>{{ $role['label'] }}</span></td>
                            <td><span class="exp-badge {{ $securityClass($user) }}">{{ $user->security_label }}</span></td>
                            <td>{{ $user->last_login_label }}</td>
                            <td>{{ $user->role_label }}</td>
                            <td><span class="status-dot {{ $statusClass($user) }}">{{ $user->status_label }}</span></td>
                            <td>{{ $user->created_at?->format('d M Y') ?? '-' }}</td>
                            <td><div class="action-group"><a class="act-btn primary" title="View profile" href="{{ route('admin.dashboard.iam.users.show', $user) }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-partner-detail"></use></svg></a><button class="act-btn danger" title="Freeze account" type="button" onclick="openFreezeModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg></button></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="empty-state"><span>No administrator accounts match the selected filters.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">
            <span class="page-info">Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} results</span>
            @if ($users->onFirstPage())<button class="page-btn" type="button" disabled>&lt;</button>@else<a class="page-btn" href="{{ $users->previousPageUrl() }}">&lt;</a>@endif
            @foreach ($users->getUrlRange(max(1, $users->currentPage() - 1), min($users->lastPage(), $users->currentPage() + 1)) as $page => $url)<a class="page-btn {{ $page === $users->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>@endforeach
            @if ($users->hasMorePages())<a class="page-btn" href="{{ $users->nextPageUrl() }}">&gt;</a>@else<button class="page-btn" type="button" disabled>&gt;</button>@endif
        </div>
    </div>

    <div class="modal-overlay" id="freezeModal"><div class="modal"><div class="modal-title">Freeze this account?</div><div class="modal-desc">The operator will immediately lose access to dashboard features. You can unfreeze the account from Account Penalty &amp; Freeze.</div><textarea class="note-area" placeholder="Reason for freeze..."></textarea><div class="modal-actions"><button class="btn-cancel" type="button" onclick="closeFreezeModal()">Cancel</button><button class="btn-danger" type="button" onclick="confirmFreeze()">Freeze Account</button></div></div></div>
@endsection

@push('scripts')
<script>
function handleRowCheck(){const checked=document.querySelectorAll('.row-check:checked').length;const bar=document.getElementById('bulkBar');const cnt=document.getElementById('bulkCount');if(checked>0){bar.classList.add('show');cnt.textContent=checked+' operator'+(checked>1?'s':'')+' selected'}else{bar.classList.remove('show')}}
function toggleSelectAll(cb){document.querySelectorAll('.row-check').forEach((c)=>c.checked=cb.checked);handleRowCheck()}
function clearSelection(){document.querySelectorAll('.row-check').forEach((c)=>c.checked=false);const selectAll=document.getElementById('selectAll');if(selectAll)selectAll.checked=false;document.getElementById('bulkBar').classList.remove('show')}
function applyBulkAction(){}
function openFreezeModal(){document.getElementById('freezeModal').classList.add('show')}
function closeFreezeModal(){document.getElementById('freezeModal').classList.remove('show')}
function confirmFreeze(){closeFreezeModal()}
</script>
@endpush
