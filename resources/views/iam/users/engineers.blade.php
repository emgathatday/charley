@extends('layouts.rebuild-dashboard')

@section('title', 'Engineer Management')

@php
    $activeTab = $filters['tab'] ?? 'all';
    $allCount = $stats['total_users'] ?? $users->total();
    $professionalCount = $stats['active_members'] ?? 0;
    $registeredCount = $stats['registered_members'] ?? 0;
    $restrictedCount = ($stats['frozen_users'] ?? 0) + ($stats['suspended_users'] ?? 0);
    $frozenCount = $stats['frozen_users'] ?? 0;
    $tabFilters = [
        'all' => ['label' => 'All Users', 'tab' => 'all', 'count' => $allCount],
        'professionals' => ['label' => 'Professionals', 'tab' => 'professional', 'count' => $professionalCount],
        'members' => ['label' => 'Registered Members', 'tab' => 'registered', 'count' => $registeredCount],
        'restricted' => ['label' => 'Restricted', 'tab' => 'restricted', 'count' => $restrictedCount],
        'frozen' => ['label' => 'Frozen', 'tab' => 'frozen', 'count' => $frozenCount],
    ];
    $initials = function ($name) {
        return collect(explode(' ', trim((string) $name)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') ?: 'U';
    };
    $avatarColor = fn ($user) => match ((int) $user->id % 8) {
        0 => '#EF4444', 1 => '#4F8DFD', 2 => '#10B981', 3 => '#F59E0B', 4 => '#6366F1', 5 => '#06B6D4', 6 => '#8B5CF6', default => '#F97316',
    };
    $roleMeta = fn ($user) => $user->role === 'professional'
        ? ['label' => 'Professional', 'class' => 'professional']
        : ['label' => 'Registered Member', 'class' => 'member'];
    $expertiseMeta = function ($user) {
        if ($user->role !== 'professional') {
            return ['label' => 'Registered Member', 'class' => 'registered'];
        }
        $years = (int) ($user->engineer_experience_years ?? 0);
        if ($years >= 15) {
            return ['label' => 'Senior Industry Expert', 'class' => 'senior'];
        }
        if ($years >= 8) {
            return ['label' => 'Experienced Professional', 'class' => 'experienced'];
        }
        return ['label' => 'Industry Professional', 'class' => 'professional2'];
    };
    $statusClass = fn ($user) => in_array($user->status, ['active', 'pending', 'suspended', 'frozen'], true) ? $user->status : 'pending';
    $stars = function ($points) {
        $filled = $points >= 10000 ? 5 : ($points >= 7500 ? 4 : ($points >= 5000 ? 3 : ($points >= 2500 ? 2 : ($points >= 1000 ? 1 : 0))));
        return $filled;
    };
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>User Management</h1>
            <p>Review member accounts, verify professional credentials, manage account types, and monitor community contributions.</p>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3">
        <div class="col">
            <div class="stat-card blue">
                <div class="stat-label">Total Users</div>
                <div class="stat-value">{{ number_format($allCount) }}</div>
                <div class="stat-sub">All registered accounts</div>
                <div class="stat-chip up">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-38-this-month-professionals-847"></use></svg>
                    {{ number_format($users->count()) }} this page
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card indigo">
                <div class="stat-label">Professionals</div>
                <div class="stat-value">{{ number_format($professionalCount) }}</div>
                <div class="stat-sub">Active verified members</div>
                <div class="stat-chip up">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-38-this-month-professionals-847"></use></svg>
                    Active
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card amber">
                <div class="stat-label">Pending Verification</div>
                <div class="stat-value">{{ number_format($stats['pending_approvals'] ?? 0) }}</div>
                <div class="stat-sub">Awaiting admin review</div>
                <div class="stat-chip warn">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-9-verifications-exceeded-the-48h"></use></svg>
                    {{ number_format($stats['pending_approvals'] ?? 0) }} pending
                </div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card">
                <div class="stat-label">Suspended / Frozen</div>
                <div class="stat-value">{{ number_format($restrictedCount) }}</div>
                <div class="stat-sub">Accounts restricted</div>
                <div class="stat-chip red">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-3-new-this-week-div"></use></svg>
                    {{ number_format($stats['suspended_users'] ?? 0) }} suspended
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs + Add User row -->
    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            <div class="tab-bar">
                @foreach ($tabFilters as $key => $tab)
                    @php $isActive = $tab['tab'] === $activeTab; @endphp
                    <a class="tab-btn {{ $isActive ? 'active' : '' }}" href="{{ route('admin.dashboard.iam.users.engineers', ['tab' => $tab['tab'], 'keyword' => $filters['keyword'] ?? '', 'account_type' => $filters['account_type'] ?? 'all', 'status' => $filters['status'] ?? 'all', 'plant_type_id' => $filters['plant_type_id'] ?? '']) }}">
                        {{ $tab['label'] }} <span class="tab-count">{{ number_format($tab['count']) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-12 col-xl-auto d-flex justify-content-xl-end">
            <button class="btn-primary" type="button" onclick="location.href='{{ route('admin.dashboard.iam.users.create-engineer') }}'">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-user"></use></svg>
                Add User
            </button>
        </div>
    </div>

    <div class="bulk-bar" id="bulkBar">
        <span class="bulk-count" id="bulkCount">0 users selected</span>
        <div class="bulk-actions"><button class="bulk-btn approve" type="button">Approve Verification</button><button class="bulk-btn suspend" type="button">Suspend</button><button class="bulk-btn export" type="button">Export Selected</button></div>
        <button type="button" onclick="clearSelection()">Clear</button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
        <form id="engineerFilterForm" method="GET" action="{{ route('admin.dashboard.iam.users.engineers') }}">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
        </form>
        <div class="table-header">

            <!-- Group 1a: Bulk Actions dropdown -->
            <div>
                <select class="filter-select" id="bulkActionSelect">
                    <option value="">Bulk Actions</option>
                    <option value="verify">Verify</option>
                    <option value="suspend">Suspend</option>
                    <option value="delete">Delete</option>
                </select>
            </div>
            <!-- Group 1b: Apply button -->
            <div>
                <button class="btn-apply" type="button" onclick="applyBulkAction()">Apply</button>
            </div>

            <!-- Group 2: Search form -->
            <div class="search-form">
                <div class="search-box">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-k-overview-a-href-admin-d"></use></svg>
                    <input form="engineerFilterForm" type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Search users...">
                </div>
                <select form="engineerFilterForm" class="filter-select" name="account_type" onchange="this.form.submit()">
                    <option value="all" @selected(($filters['account_type'] ?? 'all') === 'all')>All Account Types</option>
                    <option value="registered" @selected(($filters['account_type'] ?? 'all') === 'registered')>Registered Member</option>
                    <option value="professional" @selected(($filters['account_type'] ?? 'all') === 'professional')>Industry Professional</option>
                </select>
                <select form="engineerFilterForm" class="filter-select" name="status" onchange="this.form.submit()">
                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All Statuses</option>
                    <option value="active" @selected(($filters['status'] ?? 'all') === 'active')>Active</option>
                    <option value="pending" @selected(($filters['status'] ?? 'all') === 'pending')>Pending Verification</option>
                    <option value="suspended" @selected(($filters['status'] ?? 'all') === 'suspended')>Suspended</option>
                    <option value="frozen" @selected(($filters['status'] ?? 'all') === 'frozen')>Frozen</option>
                </select>
                <select form="engineerFilterForm" class="filter-select" name="plant_type_id" onchange="this.form.submit()">
                    <option value="">All Plants</option>
                    @foreach ($plantTypeOptions as $plantTypeId => $plantTypeName)
                        <option value="{{ $plantTypeId }}" @selected((string) ($filters['plant_type_id'] ?? '') === (string) $plantTypeId)>{{ $plantTypeName }}</option>
                    @endforeach
                </select>
                <button form="engineerFilterForm" class="btn-outline btn-filter" type="submit">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter-account-acc"></use></svg>
                    Filter
                </button>
            </div>

            <!-- Group 3: Title / meta (far right) -->
            <div class="table-title-block">
                <div class="table-title">All Users</div>
                <div class="table-meta">Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} users</div>
            </div>

        </div>

        <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                    <th>User</th>
                    <th>Account Type</th>
                    <th>Expertise Level</th>
                    <th>Contribution</th>
                    <th>Plant Type</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                @forelse ($users as $user)
                    @php
                        $role = $roleMeta($user);
                        $exp = $expertiseMeta($user);
                        $points = (($user->verification_requests_count ?? 0) * 45) + (($user->pending_verification_requests_count ?? 0) * 12) + (int) ($user->engineer_experience_years ?? $user->unverified_experience_years ?? 0) * 120;
                        $filledStars = $stars($points);
                    @endphp
                    <tr>
                        <td><input class="row-check" type="checkbox" onchange="handleRowCheck()"></td>
                        <td><div class="user-cell"><div class="user-avatar"><img class="user-avatar-img" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgcng9IjEwIiBmaWxsPSIjRjFGNEY4Ii8+PGNpcmNsZSBjeD0iNTAiIGN5PSIzOCIgcj0iMTgiIGZpbGw9IiNDOEQwREEiLz48cGF0aCBkPSJNNTAgNjBjLTIyIDAtMzQgMTQtMzQgMzJ2OGg2OHYtOGMwLTE4LTEyLTMyLTM0LTMyeiIgZmlsbD0iI0M4RDBEQSIvPjwvc3ZnPg==" alt="{{ $user->display_name }}"></div><div><a class="user-name" href="{{ route('admin.dashboard.iam.users.show', $user) }}">{{ $user->display_name }}</a><div class="user-email">{{ $user->email }}</div></div></div></td>
                        <td><span class="role-badge {{ $role['class'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>{{ $role['label'] }}</span></td>
                        <td><span class="exp-badge {{ $exp['class'] }}">{{ $exp['label'] }}</span></td>
                        <td><div class="points-cell">{{ number_format($points) }} <span>pts</span></div><div class="stars">@for ($i = 0; $i < 5; $i++)<svg class="star {{ $i < $filledStars ? '' : 'empty' }}" viewBox="0 0 24 24" fill="{{ $i < $filledStars ? '#F59E0B' : '#E2E8F0' }}" stroke="{{ $i < $filledStars ? '#F59E0B' : '#CBD5E1' }}" stroke-width="1"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>@endfor</div></td>
                        <td>{{ $user->plant_type_label }}</td>
                        <td><span class="status-dot {{ $statusClass($user) }}">{{ $user->status_label }}</span></td>
                        <td>{{ $user->created_at?->format('d M Y') ?? '-' }}</td>
                        <td><div class="action-group"><a class="act-btn primary" title="View profile" href="{{ route('admin.dashboard.iam.users.show', $user) }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-partner-detail"></use></svg></a><button class="act-btn" title="Send message" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-featured-answer-on-co-absorber"></use></svg></button><button class="act-btn danger" title="Freeze account" type="button" onclick="openFreezeModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg></button></div></td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="empty-state"><span>No engineer accounts match the selected filters.</span></div></td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="pagination">
            <span class="page-info">Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} results</span>
            @if ($users->onFirstPage())
                <button class="page-btn" type="button" disabled><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg></button>
            @else
                <a class="page-btn" href="{{ $users->previousPageUrl() }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg></a>
            @endif
            @foreach ($users->getUrlRange(max(1, $users->currentPage() - 1), min($users->lastPage(), $users->currentPage() + 1)) as $page => $url)
                <a class="page-btn {{ $page === $users->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
            @endforeach
            @if ($users->currentPage() + 1 < $users->lastPage())
                <button class="page-btn" type="button" disabled>...</button>
                <a class="page-btn" href="{{ $users->url($users->lastPage()) }}">{{ $users->lastPage() }}</a>
            @endif
            @if ($users->hasMorePages())
                <a class="page-btn" href="{{ $users->nextPageUrl() }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-pa"></use></svg></a>
            @else
                <button class="page-btn" type="button" disabled><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-pa"></use></svg></button>
            @endif
        </div>
    </div>

    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
    <div class="drawer" id="detailDrawer"><div class="drawer-head"><div><div class="drawer-title" id="drawerName">User Details</div><div class="drawer-sub" id="drawerEmail">-</div></div><button class="drawer-close" type="button" onclick="closeDrawer()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-"></use></svg></button></div><div class="drawer-body" id="drawerBody"><div class="profile-hero"><div class="profile-avatar-lg">U</div><div class="profile-hero-info"><div class="user-name">User Details</div><div class="user-email">Select a row action to review profile context.</div></div></div></div><div class="drawer-footer" id="drawerFooter"><button class="drawer-act-btn success" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg>Approve Verification</button><button class="drawer-act-btn danger" type="button" onclick="openFreezeModal()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg>Freeze Account</button><button class="drawer-act-btn" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-featured-answer-on-co-absorber"></use></svg>Message</button></div></div>
    <div class="modal-overlay" id="freezeModal"><div class="modal"><div class="modal-title">Freeze this account?</div><div class="modal-desc">The user will immediately lose access to all platform features. They will receive an email notification. You can unfreeze the account at any time from Account Penalty &amp; Freeze.</div><textarea class="note-area" placeholder="Reason for freeze (optional, included in notification email)..."></textarea><div class="modal-actions"><button class="btn-cancel" type="button" onclick="closeFreezeModal()">Cancel</button><button class="btn-danger" type="button" onclick="confirmFreeze()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg>Freeze Account</button></div></div></div>
@endsection

@push('scripts')
<script>
function handleRowCheck(){const checked=document.querySelectorAll('.row-check:checked').length;const bar=document.getElementById('bulkBar');const cnt=document.getElementById('bulkCount');if(checked>0){bar.classList.add('show');cnt.textContent=checked+' user'+(checked>1?'s':'')+' selected'}else{bar.classList.remove('show')}}
function toggleSelectAll(cb){document.querySelectorAll('.row-check').forEach((c)=>c.checked=cb.checked);handleRowCheck()}
function clearSelection(){document.querySelectorAll('.row-check').forEach((c)=>c.checked=false);const selectAll=document.getElementById('selectAll');if(selectAll)selectAll.checked=false;document.getElementById('bulkBar').classList.remove('show')}
function closeDrawer(){document.getElementById('drawerOverlay').classList.remove('show');document.getElementById('detailDrawer').classList.remove('open')}
function openFreezeModal(){document.getElementById('freezeModal').classList.add('show')}
function closeFreezeModal(){document.getElementById('freezeModal').classList.remove('show')}
function confirmFreeze(){closeFreezeModal()}
</script>
@endpush





