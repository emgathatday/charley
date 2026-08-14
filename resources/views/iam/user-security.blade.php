@extends('layouts.rebuild-dashboard')

@section('title', 'Account Penalty & Freeze')

@section('content')
@php
    $penaltyRows = collect($penaltyRows ?? $accountPenalties ?? []);
    $verificationByUser = collect($latestVerifications ?? [])->keyBy('user_id');
    $viewErrors = $errors ?? new Illuminate\Support\ViewErrorBag;
    $rowCount = $penaltyRows->count();
    $uniqueUserCount = $penaltyRows->pluck('user_id')->unique()->count();
    $frozenCount = $penaltyRows->whereIn('action_type', ['account_freeze', 'self_freeze'])->count();
    $suspendedCount = $penaltyRows->where('action_type', 'temporary_suspension')->count();
    $warningCount = $penaltyRows->where('action_type', 'warning')->count();
    $pendingCount = $verificationByUser->where('status', 'pending')->count();
    $statusCounts = $rowCount > 0
        ? ['frozen' => $frozenCount, 'suspended' => $suspendedCount, 'warned' => $warningCount, 'pending' => $pendingCount]
        : ['frozen' => 4, 'suspended' => 6, 'warned' => 18, 'pending' => 3];
    $chipClasses = ['advertising' => 'chip-ads', 'spam' => 'chip-spam', 'abuse' => 'chip-abuse', 'impersonation' => 'chip-impersonation', 'commercial' => 'chip-commercial', 'self' => 'chip-self'];
    $actionControlsFor = function (string $actionType): array {
        return match ($actionType) {
            'warning' => [['label' => 'Escalate', 'class' => 'btn-warning'], ['label' => 'Freeze', 'class' => 'btn-danger']],
            'temporary_suspension' => [['label' => 'Lift Suspension', 'class' => 'btn-success-outline'], ['label' => 'Freeze', 'class' => 'btn-danger']],
            'account_freeze' => [['label' => 'Lift Freeze', 'class' => 'btn-success-outline'], ['label' => 'Review Freeze', 'class' => 'btn-ghost']],
            'unfreeze' => [['label' => 'Freeze', 'class' => 'btn-danger'], ['label' => 'Issue Warning', 'class' => 'btn-warning']],
            'ban' => [['label' => 'Review Ban', 'class' => 'btn-ghost'], ['label' => 'Restore Review', 'class' => 'btn-success-outline']],
            'self_freeze' => [['label' => 'Lift Freeze', 'class' => 'btn-success-outline'], ['label' => 'Review Self Freeze', 'class' => 'btn-ghost']],
            'self_unfreeze' => [['label' => 'Freeze', 'class' => 'btn-danger'], ['label' => 'Issue Warning', 'class' => 'btn-warning']],
            default => [['label' => 'Review', 'class' => 'btn-ghost']],
        };
    };
    $demoPenaltyRows = [
        ['initials' => 'KH', 'avatar' => 'account-avatar-kh', 'name' => 'Khalid Habib', 'email' => 'k.habib@petrosyntech.com', 'role' => 'Professional', 'status' => 'Frozen', 'statusClass' => 'frozen', 'violation' => 'Unauthorized advertising', 'chip' => 'chip-ads', 'duration' => 'Indefinite', 'initiated' => 'Admin - Sara R.', 'primaryAction' => 'Lift Freeze'],
        ['initials' => 'MS', 'avatar' => 'account-avatar-ms', 'name' => 'Mei Sorenson', 'email' => 'mei.sorenson@gmail.com', 'role' => 'Unverified Member', 'status' => 'Suspended', 'statusClass' => 'suspended', 'violation' => 'Spam / repetitive posting', 'chip' => 'chip-spam', 'duration' => '14 days - Expires 28 Jul 2026', 'initiated' => 'Admin - Sara R.', 'primaryAction' => 'Escalate'],
        ['initials' => 'PN', 'avatar' => 'account-avatar-pn', 'name' => 'Paulo Nakamura', 'email' => 'p.nakamura@amtech.com.br', 'role' => 'Professional', 'status' => 'Warned', 'statusClass' => 'warned', 'violation' => 'Commercial misuse', 'chip' => 'chip-commercial', 'duration' => 'Warning issued 10 Jul 2026', 'initiated' => 'Admin - Sara R.', 'primaryAction' => 'Escalate'],
        ['initials' => 'AL', 'avatar' => 'account-avatar-al', 'name' => 'Anita Larsson', 'email' => 'a.larsson@nitro-consult.se', 'role' => 'Professional', 'status' => 'Frozen', 'statusClass' => 'frozen', 'violation' => 'Self-requested', 'chip' => 'chip-self', 'duration' => 'User-initiated - No expiry', 'initiated' => 'User self-request', 'primaryAction' => null],
        ['initials' => 'RT', 'avatar' => 'account-avatar-rt', 'name' => 'Reza Taheri', 'email' => 'r.taheri@fajrpetro.ir', 'role' => 'Professional', 'status' => 'Warned', 'statusClass' => 'warned', 'violation' => 'Abusive behaviour', 'chip' => 'chip-abuse', 'duration' => 'Warning issued 8 Jul 2026', 'initiated' => 'Admin - Sara R.', 'primaryAction' => 'Escalate'],
        ['initials' => 'JV', 'avatar' => 'account-avatar-jv', 'name' => 'Johan Vermeulen', 'email' => 'j.vermeulen@sasolenergy.co.za', 'role' => 'Professional', 'status' => 'Suspended', 'statusClass' => 'suspended', 'violation' => 'Impersonation', 'chip' => 'chip-impersonation', 'duration' => '30 days - Expires 8 Aug 2026', 'initiated' => 'Admin - Sara R.', 'primaryAction' => 'Freeze'],
    ];
    $penaltyStatCards = [
        ['class' => 'red', 'label' => 'Frozen Accounts', 'value' => $statusCounts['frozen'], 'sub' => 'Account-freeze rows'],
        ['class' => 'orange', 'label' => 'Active Suspensions', 'value' => $statusCounts['suspended'], 'sub' => 'Temporary suspension rows'],
        ['class' => 'amber', 'label' => 'Warnings Issued', 'value' => $statusCounts['warned'], 'sub' => 'Warning rows'],
        ['class' => 'blue', 'label' => 'Pending Review', 'value' => $statusCounts['pending'], 'sub' => 'Latest verification queue'],
    ];
@endphp

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($viewErrors->any())<div class="alert alert-danger">{{ $viewErrors->first() }}</div>@endif

    <div class="page-head">
        <div><div class="page-title">Account Penalty &amp; Freeze</div><div class="page-subtitle">Manage warnings, temporary suspensions, and account freezes for members and professionals.</div></div>
        <div class="page-head-actions">
            <button class="btn btn-ghost" onclick="openPanel(null)" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-k-overview-a-href-admin-d') }}"></use></svg>Search account</button>
            <button class="btn btn-danger" onclick="openPanel('new')" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-account-penalty-and-freeze-3') }}"></use></svg>Apply Penalty</button>
        </div>
    </div>

    {{ \App\Support\AdminStatCards::render($penaltyStatCards) }}

    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            <div class="tab-bar account-penalty-tab-bar">
                <button class="tab-btn active" onclick="switchTab(this,'all')" type="button">All Accounts <span class="tab-count">{{ $rowCount ?: 28 }}</span></button>
                <button class="tab-btn" onclick="switchTab(this,'frozen')" type="button">Frozen <span class="tab-count">{{ $statusCounts['frozen'] }}</span></button>
                <button class="tab-btn" onclick="switchTab(this,'suspended')" type="button">Suspended <span class="tab-count">{{ $statusCounts['suspended'] }}</span></button>
                <button class="tab-btn" onclick="switchTab(this,'warned')" type="button">Warned <span class="tab-count">{{ $statusCounts['warned'] }}</span></button>
                <button class="tab-btn" onclick="switchTab(this,'pending')" type="button">Pending Review <span class="tab-count">{{ $statusCounts['pending'] }}</span></button>
            </div>
        </div>
    </div>
    <div class="table-wrap account-penalty-table-wrap">
        <div class="table-header">
            <div class="bulk-action-block"><select class="filter-select" id="bulkActionSelect"><option value="">Bulk Actions</option><option value="lift">Lift restriction</option><option value="warning">Issue warning</option><option value="suspend">Suspend</option><option value="freeze">Freeze</option></select></div>
            <div class="bulk-apply-block"><button class="btn-apply" onclick="applyBulkAction()" type="button">Apply</button></div>
            <form class="search-form" method="GET" action="{{ route('admin.dashboard.iam.account-penalty-freeze') }}">
                <div class="search-box account-search-box"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-k-overview-a-href-admin-d') }}"></use></svg><input type="text" name="search" value="{{ request('search') }}" placeholder="Search accounts..."></div>
                <select class="filter-select" name="role"><option value="">All Account Types</option>@foreach (['unverified_member' => 'Unverified Member', 'professional' => 'Professional', 'partner' => 'Partner', 'admin' => 'Admin'] as $roleValue => $roleLabel)<option value="{{ $roleValue }}" @selected(request('role') === $roleValue)>{{ $roleLabel }}</option>@endforeach</select>
                <select class="filter-select" name="violation"><option value="">All Violation Types</option><option value="advertising" @selected(request('violation') === 'advertising')>Unauthorized advertising</option><option value="spam" @selected(request('violation') === 'spam')>Spam / repetitive posting</option><option value="abuse" @selected(request('violation') === 'abuse')>Abusive behaviour</option><option value="impersonation" @selected(request('violation') === 'impersonation')>Impersonation</option><option value="commercial" @selected(request('violation') === 'commercial')>Commercial misuse</option></select>
                <select class="filter-select" name="status"><option value="">All Statuses</option>@foreach (['frozen' => 'Frozen', 'suspended' => 'Suspended', 'warned' => 'Warned'] as $statusValue => $statusLabel)<option value="{{ $statusValue }}" @selected(request('status') === $statusValue)>{{ $statusLabel }}</option>@endforeach</select>
                <button class="btn-outline btn-filter" type="submit"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-filter-account-acc') }}"></use></svg>Filter</button>
            </form>
            <div class="table-title-block"><div class="table-title">All Penalty Accounts</div><div class="table-meta">Showing {{ $rowCount ?: 6 }} of {{ $rowCount ?: 28 }} penalty rows{{ $uniqueUserCount ? ' across ' . $uniqueUserCount . ' accounts' : '' }}</div></div>
        </div>

        <div class="table-scroll"><table class="account-penalty-table"><thead><tr><th class="account-check-col"><input type="checkbox" class="table-check" id="selectAllPenalty" onchange="toggleSelectAll(this)"></th><th>Account</th><th>Account Type</th><th>Status</th><th>Violation</th><th>Duration / Expiry</th><th>Initiated by</th><th>Actions</th></tr></thead><tbody>
            @forelse ($penaltyRows as $penaltyRow)
                @php
                    $name = trim(($penaltyRow->user_first_name ?? '') . ' ' . ($penaltyRow->user_last_name ?? '')) ?: ($penaltyRow->user_username ?? 'Unknown account');
                    $initials = collect(explode(' ', $name))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') ?: 'AC';
                    $actionType = $penaltyRow->action_type ?? 'warning';
                    $statusClass = match ($actionType) {'account_freeze', 'self_freeze', 'ban' => 'frozen', 'temporary_suspension' => 'suspended', default => 'warned'};
                    $violation = $penaltyRow->reason ?? 'TODO: No confirmed penalty reason';
                    $violationKey = Illuminate\Support\Str::of($violation)->lower();
                    $chipClass = collect($chipClasses)->first(fn ($class, $key) => $violationKey->contains($key), $actionType === 'self_freeze' ? 'chip-self' : 'chip-commercial');
                    $duration = $penaltyRow->ends_at ? $penaltyRow->ends_at->format('d M Y') : (in_array($actionType, ['account_freeze', 'self_freeze', 'ban'], true) ? 'No expiry' : (($penaltyRow->duration_days ?? null) ? $penaltyRow->duration_days . ' days' : 'No active expiry'));
                    $actionControls = $actionControlsFor($actionType);
                @endphp
                <tr data-account-status="{{ $statusClass }}" data-penalty-id="{{ $penaltyRow->id }}">
                    <td class="account-check-col"><input type="checkbox" class="table-check account-row-check"></td>
                    <td><div class="user-cell"><div class="user-avatar">{{ $initials }}</div><div><a class="user-name-link" href="{{ route('admin.dashboard.iam.account-penalty-freeze.show', ['user' => $penaltyRow->user_id]) }}">{{ $name }}</a><div class="user-meta">{{ $penaltyRow->user_email }}</div></div></div></td>
                    <td><span class="cell-text cell-strong">{{ str_replace('_', ' ', $penaltyRow->user_role ?? '-') }}</span></td>
                    <td><span class="badge {{ $statusClass }}"><span class="badge-dot"></span>{{ ucfirst($statusClass) }}</span></td>
                    <td><span class="penalty-chip {{ $chipClass }}">{{ $violation }}</span><div class="user-meta">{{ str_replace('_', ' ', $actionType) }}</div></td>
                    <td><span class="cell-text">{{ $duration }}</span></td>
                    <td><span class="cell-text">{{ $penaltyRow->admin_id ? 'Admin #' . $penaltyRow->admin_id : 'TODO: source pending' }}</span></td>
                    <td><div class="action-cell"><a class="btn btn-ghost btn-sm" href="{{ route('admin.dashboard.iam.account-penalty-freeze.show', ['user' => $penaltyRow->user_id]) }}">View</a>@foreach ($actionControls as $control)<button class="btn {{ $control['class'] }} btn-sm" onclick="openPanelForUser({{ Illuminate\Support\Js::from($initials) }}, {{ Illuminate\Support\Js::from($name) }}, {{ Illuminate\Support\Js::from(str_replace('_', ' ', $penaltyRow->user_role ?? '-')) }}, {{ Illuminate\Support\Js::from(ucfirst($statusClass)) }}, {{ Illuminate\Support\Js::from($control['label']) }})" type="button">{{ $control['label'] }}</button>@endforeach</div></td>
                </tr>
            @empty
                @foreach ($demoPenaltyRows as $row)
                    <tr data-account-status="{{ $row['statusClass'] }}"><td class="account-check-col"><input type="checkbox" class="table-check account-row-check"></td><td><div class="user-cell"><div class="user-avatar {{ $row['avatar'] }}">{{ $row['initials'] }}</div><div><div class="user-name-link" onclick="goToDetail()">{{ $row['name'] }}</div><div class="user-meta">{{ $row['email'] }}</div></div></div></td><td><span class="cell-text cell-strong">{{ $row['role'] }}</span></td><td><span class="badge {{ $row['statusClass'] }}"><span class="badge-dot"></span>{{ $row['status'] }}</span></td><td><span class="penalty-chip {{ $row['chip'] }}">{{ $row['violation'] }}</span></td><td><span class="cell-text">{{ $row['duration'] }}</span></td><td><span class="cell-text">{{ $row['initiated'] }}</span></td><td><div class="action-cell"><button class="btn btn-ghost btn-sm" onclick="goToDetail()" type="button">View</button>@if ($row['primaryAction'])<button class="btn {{ $row['primaryAction'] === 'Freeze' ? 'btn-danger' : ($row['primaryAction'] === 'Lift Freeze' ? 'btn-success-outline' : 'btn-warning') }} btn-sm" onclick="openPanel(null)" type="button">{{ $row['primaryAction'] }}</button>@endif</div></td></tr>
                @endforeach
            @endforelse
        </tbody></table></div>
        <div class="pagination"><span class="page-info">Showing {{ $rowCount ?: 6 }} of {{ $rowCount ?: 28 }} results</span><button class="page-btn" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-back-to-account-penalty-and') }}"></use></svg></button><button class="page-btn active" type="button">1</button><button class="page-btn" type="button">2</button><button class="page-btn" type="button">3</button><button class="page-btn" type="button">4</button><button class="page-btn" type="button">5</button><button class="page-btn" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-account-penalty-and-freeze-pa') }}"></use></svg></button></div>
    </div>


<div class="panel-overlay" id="panelOverlay" onclick="closePanel()"></div><div class="action-panel" id="actionPanel"><div class="panel-header"><div class="panel-title" id="panelTitle">Apply Penalty</div><button class="panel-close" onclick="closePanel()" type="button"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-button-class-btn-btn-ghost-style-flex-1-onclick-') }}"></use></svg></button></div><div class="panel-body" id="panelBody"><div class="form-group"><label class="form-label">Search Account</label><input class="form-input" placeholder="Name, email or company..."><div class="form-hint">Type to search for a member, professional, or partner account.</div></div><div class="form-group"><label class="form-label">Violation Type</label><select class="form-select"><option>Unauthorized advertising / commercial post</option><option>Spam / repetitive posting</option><option>Abusive or offensive behaviour</option><option>Impersonation</option></select></div><div class="form-group"><label class="form-label">Internal Notes</label><textarea class="form-textarea" rows="4" placeholder="Describe the reason for this action."></textarea></div></div><div class="panel-footer" id="panelFooter"><button class="btn btn-ghost panel-footer-cancel" onclick="closePanel()" type="button">Cancel</button><button class="btn btn-danger panel-footer-confirm" id="panelConfirmBtn" onclick="confirmAction()" type="button">Confirm Action</button></div></div>
<div class="toast" id="toast"><svg class="icon"><use href="{{ asset('assets/icons/sprite.svg#icon-profile-verification-queue-5-svg') }}"></use></svg><span id="toastMsg">Action applied.</span></div>
@endsection
@push('scripts')
<script>
    const panelOverlay = document.getElementById('panelOverlay'); const actionPanel = document.getElementById('actionPanel'); const panelTitle = document.getElementById('panelTitle'); const panelBody = document.getElementById('panelBody'); const panelConfirmBtn = document.getElementById('panelConfirmBtn'); const defaultPanelBody = panelBody ? panelBody.innerHTML : '';
    window.openPanel = function (mode) { if (!actionPanel || !panelOverlay || !panelBody) return; panelTitle.textContent = mode === 'new' ? 'Apply Penalty' : 'Search Account'; panelConfirmBtn.textContent = mode === 'new' ? 'Confirm Action' : 'Search'; panelBody.innerHTML = defaultPanelBody; actionPanel.classList.add('open'); panelOverlay.classList.add('open'); };
    window.openPanelForUser = function (initials, name, role, status, actionLabel = 'Review') { if (!actionPanel || !panelOverlay || !panelBody) return; panelTitle.textContent = actionLabel; panelConfirmBtn.textContent = actionLabel + ' Preview'; panelBody.innerHTML = `<div class="user-cell mb-3"><div class="user-avatar">${initials}</div><div><div class="user-name-link">${name}</div><div class="user-meta">${role} - ${status} - ${actionLabel}</div></div></div>${defaultPanelBody}`; actionPanel.classList.add('open'); panelOverlay.classList.add('open'); };
    window.closePanel = function () { actionPanel?.classList.remove('open'); panelOverlay?.classList.remove('open'); };
    window.switchTab = function (button, status) { document.querySelectorAll('.account-penalty-tab-bar .tab-btn').forEach((tab) => tab.classList.remove('active')); button.classList.add('active'); document.querySelectorAll('.account-penalty-table tbody tr').forEach((row) => { row.hidden = status !== 'all' && row.dataset.accountStatus !== status; }); };
    window.toggleSelectAll = function (source) { document.querySelectorAll('.account-row-check').forEach((checkbox) => { checkbox.checked = source.checked; }); };
    window.goToDetail = function () { window.location.href = "{{ route('admin.dashboard.iam.account-penalty-freeze.show', ['user' => $penaltyRows->first()?->user_id ?? 1]) }}"; };
    window.applyBulkAction = function () { showToast('Bulk action preview only. Confirmed write contract is required.'); };
    window.confirmAction = function () { closePanel(); showToast('Action preview applied.'); };
    window.showToast = function (message) { const toast = document.getElementById('toast'); const toastMsg = document.getElementById('toastMsg'); if (!toast || !toastMsg) return; toastMsg.textContent = message; toast.classList.add('show'); window.setTimeout(() => toast.classList.remove('show'), 2600); };
</script>
@endpush