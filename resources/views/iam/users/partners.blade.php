@extends('layouts.rebuild-dashboard')

@section('title', 'Partner Management')

@section('content')
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

    <x-admin.stat-cards :items="$partnerStatCards" row-class="row g-3 mb-3" />

    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            <x-admin.tab-bar :items="$partnerTabBar" />
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
                <button class="btn-apply" type="button" data-bulk-apply>Apply</button>
                <div class="search-box">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-search-2"></use></svg>
                    <input type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Search company name, website, or country...">
                </div>
                <select class="native-select js-auto-submit" name="subscription_tier_id">
                    <option value="">Tier: All</option>
                    @foreach ($subscriptionTierOptions as $tierId => $tierName)
                        <option value="{{ $tierId }}" @selected($activeTierId === (string) $tierId)>Tier: {{ $tierName }}</option>
                    @endforeach
                </select>
                <select class="native-select js-auto-submit" name="plant_type_id">
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
                        <th class="checkbox-th"><input type="checkbox" class="select-all-check" id="selectAllCheckbox"></th>
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
                        <tr>
                            <td class="checkbox-td"><input type="checkbox" class="row-check"></td>
                            <td>
                                <div class="company-cell">
                                    @if ($user->partner_tier_code === 'inactive')
                                        <div class="company-avatar-default"><img src="https://api.dicebear.com/7.x/initials/svg?seed={{ $user->partner_logo_seed }}&amp;backgroundColor=transparent&amp;textColor=cbd5e1&amp;fontWeight=600" alt="Default partner logo"></div>
                                    @else
                                        <div class="company-logo"><img src="https://api.dicebear.com/7.x/shapes/svg?seed={{ $user->partner_logo_seed }}&amp;backgroundColor=transparent" alt="{{ $user->partner_company_display_name }} logo"></div>
                                    @endif
                                    <div>
                                        <a class="company-name" href="{{ route('admin.dashboard.iam.users.show', $user) }}">{{ $user->partner_company_display_name }}</a>
                                        <div class="company-meta">{{ $user->partner_company_meta }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="tier-badge tier-{{ $user->partner_tier_code }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-billing"></use></svg>{{ $user->partner_tier_label }}</span></td>
                            <td>{{ $user->plant_type_label }}</td>
                            <td><span class="status-pill {{ $user->partner_verification_class }}"><span class="dot"></span>{{ $user->partner_verification_label }}</span></td>
                            <td><span class="status-pill {{ $user->partner_subscription_class }}"><span class="dot"></span>{{ $user->partner_subscription_label }}</span></td>
                            <td>{{ $user->created_at?->format('M j, Y') ?? '-' }}</td>
                            <td>
                                <div class="row-actions">
                                    @if ($user->partner_is_review)
                                        <button class="review-btn" type="button" data-drawer-open>
                                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-partner-detail"></use></svg>
                                            Review
                                        </button>
                                    @else
                                        <a class="act-btn" href="{{ route('admin.dashboard.iam.users.show', $user) }}" title="View partner detail"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-partner-detail"></use></svg></a>
                                        <a class="act-btn" href="{{ route('admin.dashboard.iam.users.edit-partner', $user) }}" title="Edit partner"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-3"></use></svg></a>
                                        <button class="act-btn danger" type="button" title="Freeze account"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg></button>
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

    <div class="drawer-overlay" id="drawerOverlay" data-drawer-close></div>
    <div class="drawer" id="reviewDrawer">
        <div class="drawer-head"><div class="drawer-head-company"><div class="company-logo">CC</div><div><h3 id="drawerCompanyName">Partner Review</h3><div class="company-meta" id="drawerCompanyMeta">-</div></div></div><button class="drawer-close" type="button" data-drawer-close>x</button></div>
        <div class="drawer-body"><div class="drawer-section"><h4>Company verification</h4><div class="info-grid"><div class="info-item"><div class="k">Submitted</div><div class="v">{{ now()->format('M j, Y') }}</div></div><div class="info-item"><div class="k">Active Subscription</div><div class="v">Module 04</div></div></div></div></div>
        <div class="drawer-footer"><button class="drawer-act-btn success" type="button">Approve Partner</button><button class="drawer-act-btn danger" type="button">Reject</button></div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/partner-management.js') }}"></script>
@endpush
