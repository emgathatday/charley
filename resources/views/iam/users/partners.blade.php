@extends('layouts.rebuild-dashboard')

@section('title', 'Partner Management')

@php
    $activeTab = $filters['tab'] ?? 'all';
    $activeTierId = (string) ($filters['subscription_tier_id'] ?? '');
    $allCount = $stats['total_users'] ?? $users->total();
    $pendingCount = $stats['pending_approvals'] ?? 0;
    $suspendedCount = ($stats['suspended_users'] ?? 0) + ($stats['frozen_users'] ?? 0);
    $tierItems = collect($tierStats ?? []);
    $activeSubscriptionCount = $tierItems->sum('count');
    $initials = function ($name) {
        return collect(explode(' ', trim((string) $name)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') ?: 'P';
    };
    $companyName = fn ($user) => $user->partner_company_name ?: $user->display_name;
    $companyMeta = fn ($user) => trim(($user->partner_website ?: $user->email).' - '.($user->partner_country ?: $user->plant_type_label), ' -');
    $tierCode = fn ($user) => strtolower((string) ($user->partner_tier_code ?: 'inactive'));
    $tierLabel = fn ($user) => $user->partner_tier_label ?: 'No active tier';
    $verificationLabel = fn ($user) => $user->partner_approval_status ? str_replace('_', ' ', ucfirst((string) $user->partner_approval_status)) : ($user->status === 'active' ? 'Verified' : $user->status_label);
    $verificationClass = fn ($user) => match ((string) ($user->partner_approval_status ?: $user->status)) {
        'approved', 'active' => 'status-active',
        'pending' => 'status-pending',
        'suspended', 'frozen', 'rejected' => 'status-suspended',
        default => 'status-pending',
    };
    $subscriptionLabel = fn ($user) => match ((string) ($user->partner_subscription_status ?: $user->status)) {
        'active' => 'Active',
        'pending_approval' => 'Pending approval',
        'suspended', 'frozen' => 'Frozen',
        'inactive' => 'Not activated',
        default => ucfirst(str_replace('_', ' ', (string) ($user->partner_subscription_status ?: 'Not activated'))),
    };
    $subscriptionClass = fn ($user) => match ((string) ($user->partner_subscription_status ?: $user->status)) {
        'active' => 'status-active',
        'suspended', 'frozen', 'cancelled', 'rejected', 'expired' => 'status-suspended',
        default => 'status-pending',
    };
    $partnerStatCards = [
        ['icon' => 'icon-partners', 'value' => number_format($allCount), 'label' => 'Total Partners', 'trend' => number_format($users->count()) . ' this page'],
        ['icon' => 'icon-month', 'value' => number_format($activeSubscriptionCount), 'label' => 'Active Subscriptions', 'trend' => 'Module 04 source'],
        ['icon' => 'icon-clock', 'value' => number_format($pendingCount), 'label' => 'Pending Approval', 'trend' => 'Awaiting review'],
        ['icon' => 'icon-lock', 'value' => number_format($suspendedCount), 'label' => 'Restricted', 'trend' => 'Frozen or suspended'],
        ['icon' => 'icon-billing', 'value' => number_format(count($subscriptionTierOptions ?? [])), 'label' => 'Active Tiers', 'trend' => 'Dynamic catalog'],
    ];
@endphp

@section('content')
    <!-- Page header -->
    <div class="page-head">
        <div>
            <h1>Partner Management</h1>
            <p>Review company verification, approve new partner accounts, assign Diamond / Gold / Platinum tiers, and manage subscription status.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('admin.dashboard.iam.users.create-partner') }}" class="btn-primary">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-another-document-clas"></use></svg>
                Add Partner Manually
            </a>
        </div>
    </div>

    <!-- Stats -->
    {{ \App\Support\AdminStatCards::render(['row_class' => 'row g-3 mb-3', 'cards' => $partnerStatCards]) }}

    <!-- Toolbar: tabs -->
    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            <div class="tab-bar">
                <a class="tab-btn {{ $activeTab === 'all' && $activeTierId === '' ? 'active' : '' }}" href="{{ route('admin.dashboard.iam.users.partners', ['tab' => 'all', 'keyword' => $filters['keyword'] ?? '', 'plant_type_id' => $filters['plant_type_id'] ?? '']) }}">
                    All Partners <span class="tab-count">{{ number_format($allCount) }}</span>
                </a>
                <a class="tab-btn {{ $activeTab === 'pending' ? 'active' : '' }}" href="{{ route('admin.dashboard.iam.users.partners', ['tab' => 'pending', 'keyword' => $filters['keyword'] ?? '', 'plant_type_id' => $filters['plant_type_id'] ?? '']) }}">
                    Pending Approval <span class="tab-count">{{ number_format($pendingCount) }}</span>
                </a>
                @foreach ($tierItems as $tierId => $tier)
                    <a class="tab-btn {{ $activeTierId === (string) $tierId ? 'active' : '' }}" href="{{ route('admin.dashboard.iam.users.partners', ['tab' => 'all', 'subscription_tier_id' => $tierId, 'keyword' => $filters['keyword'] ?? '', 'plant_type_id' => $filters['plant_type_id'] ?? '']) }}">
                        {{ $tier['label'] }} <span class="tab-count">{{ number_format($tier['count']) }}</span>
                    </a>
                @endforeach
                <a class="tab-btn {{ $activeTab === 'suspended' ? 'active' : '' }}" href="{{ route('admin.dashboard.iam.users.partners', ['tab' => 'suspended', 'keyword' => $filters['keyword'] ?? '', 'plant_type_id' => $filters['plant_type_id'] ?? '']) }}">
                    Suspended <span class="tab-count">{{ number_format($suspendedCount) }}</span>
                </a>
            </div>
        </div>
    </div>

    <div class="table-card">
        <form class="table-header" method="GET" action="{{ route('admin.dashboard.iam.users.partners') }}">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="filter-group">
                <select class="native-select" id="bulkActionsSelect">
                    <option value="">Bulk Actions</option>
                    <option value="Approve">Approve</option>
                    <option value="Suspend">Suspend</option>
                    <option value="Freeze">Freeze</option>
                    <option value="Export">Export</option>
                    <option value="Delete">Delete</option>
                </select>
                <button class="btn-apply" type="button" onclick="applyBulkAction()">Apply</button>
                <div class="search-box">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-search-2"></use></svg>
                    <input type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Search company name, website, or country...">
                </div>
                <select class="native-select" name="subscription_tier_id" onchange="this.form.submit()">
                    <option value="">Tier: All</option>
                    @foreach ($subscriptionTierOptions as $tierId => $tierName)
                        <option value="{{ $tierId }}" @selected($activeTierId === (string) $tierId)>Tier: {{ $tierName }}</option>
                    @endforeach
                </select>
                <select class="native-select" name="plant_type_id" onchange="this.form.submit()">
                    <option value="">Plant Type: All</option>
                    @foreach ($plantTypeOptions as $plantTypeId => $plantTypeName)
                        <option value="{{ $plantTypeId }}" @selected((string) ($filters['plant_type_id'] ?? '') === (string) $plantTypeId)>Plant Type: {{ $plantTypeName }}</option>
                    @endforeach
                </select>
                <button class="btn-filter" type="submit">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter-2"></use></svg>
                    Filter
                </button>
            </div>
            <div class="toolbar-meta">
                <div class="toolbar-meta-title">All Partners</div>
                <div class="toolbar-meta-sub">Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} partners</div>
            </div>
        </form>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-th"><input type="checkbox" class="select-all-check" id="selectAllCheckbox" onchange="toggleSelectAll(this)"></th>
                        <th>Company</th>
                        <th>Tier</th>
                        <th>Plant Type</th>
                        <th>Verification</th>
                        <th>Subscription</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $code = $tierCode($user);
                            $isReview = in_array($verificationClass($user), ['status-pending'], true);
                            $logoSeed = rawurlencode($companyName($user));
                        @endphp
                        <tr>
                            <td class="checkbox-td"><input type="checkbox" class="row-check" onchange="updateSelectAllState()"></td>
                            <td>
                                <div class="company-cell">
                                    @if ($code === 'inactive')
                                        <div class="company-avatar-default"><img src="https://api.dicebear.com/7.x/initials/svg?seed={{ $logoSeed }}&amp;backgroundColor=transparent&amp;textColor=cbd5e1&amp;fontWeight=600" alt="Default partner logo"></div>
                                    @else
                                        <div class="company-logo"><img src="https://api.dicebear.com/7.x/shapes/svg?seed={{ $logoSeed }}&amp;backgroundColor=transparent" alt="{{ $companyName($user) }} logo"></div>
                                    @endif
                                    <div>
                                        <a class="company-name" href="{{ route('admin.dashboard.iam.users.show', $user) }}">{{ $companyName($user) }}</a>
                                        <div class="company-meta">{{ $companyMeta($user) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="tier-badge tier-{{ $code }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-billing"></use></svg>{{ $tierLabel($user) }}</span></td>
                            <td>{{ $user->plant_type_label }}</td>
                            <td><span class="status-pill {{ $verificationClass($user) }}"><span class="dot"></span>{{ $verificationLabel($user) }}</span></td>
                            <td><span class="status-pill {{ $subscriptionClass($user) }}"><span class="dot"></span>{{ $subscriptionLabel($user) }}</span></td>
                            <td>{{ $user->created_at?->format('M j, Y') ?? '-' }}</td>
                            <td>
                                <div class="row-actions">
                                    @if ($isReview)
                                        <button class="review-btn" type="button" onclick="openDrawer(this)">
                                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-partner-detail"></use></svg>
                                            Review
                                        </button>
                                    @else
                                        <a class="action-btn" href="{{ route('admin.dashboard.iam.users.show', $user) }}" title="View partner detail"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-partner-detail"></use></svg></a>
                                        <a class="action-btn" href="{{ route('admin.dashboard.iam.users.edit-partner', $user) }}" title="Edit partner"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-3"></use></svg></a>
                                        <button class="action-btn danger" type="button" title="Freeze account"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty-state"><span>No partner accounts match the selected filters.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-foot">
            <div class="table-foot-info">Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} partners</div>
            <div class="pager">
                @if ($users->onFirstPage())
                    <button type="button" disabled>&lt;</button>
                @else
                    <a href="{{ $users->previousPageUrl() }}">&lt;</a>
                @endif
                @foreach ($users->getUrlRange(max(1, $users->currentPage() - 1), min($users->lastPage(), $users->currentPage() + 1)) as $page => $url)
                    <a class="{{ $page === $users->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                @endforeach
                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}">&gt;</a>
                @else
                    <button type="button" disabled>&gt;</button>
                @endif
            </div>
        </div>
    </div>

    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
    <div class="drawer" id="reviewDrawer">
        <div class="drawer-head"><div class="drawer-head-company"><div class="company-logo">CC</div><div><h3 id="drawerCompanyName">Partner Review</h3><div class="company-meta" id="drawerCompanyMeta">-</div></div></div><button class="drawer-close" type="button" onclick="closeDrawer()">x</button></div>
        <div class="drawer-body"><div class="drawer-section"><h4>Company verification</h4><div class="info-grid"><div class="info-item"><div class="k">Submitted</div><div class="v">{{ now()->format('M j, Y') }}</div></div><div class="info-item"><div class="k">Active Subscription</div><div class="v">Module 04</div></div></div></div></div>
        <div class="drawer-footer"><button class="drawer-act-btn success" type="button">Approve Partner</button><button class="drawer-act-btn danger" type="button">Reject</button></div>
    </div>
@endsection

@push('scripts')
<script>
function applyBulkAction(){}
function updateSelectAllState(){}
function toggleSelectAll(cb){document.querySelectorAll('.row-check').forEach((row)=>row.checked=cb.checked)}
function openDrawer(btn){const row=btn.closest('tr');document.getElementById('drawerCompanyName').textContent=row.querySelector('.company-name').textContent;document.getElementById('drawerCompanyMeta').textContent=row.querySelector('.company-meta').textContent;document.getElementById('reviewDrawer').classList.add('show');document.getElementById('drawerOverlay').classList.add('show')}
function closeDrawer(){document.getElementById('reviewDrawer').classList.remove('show');document.getElementById('drawerOverlay').classList.remove('show')}
</script>
@endpush

