@extends('layouts.rebuild-dashboard')

@section('title', 'Engineer Management')

@section('content')
    <div class="page-head">
        <div>
            <h1>User Management</h1>
            <p>Review member accounts, verify professional credentials, manage account types, and monitor community contributions.</p>
        </div>
    </div>

    <x-admin.stat-cards :items="$engineerStatCards" />

    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            <x-admin.tab-bar :items="$engineerTabBar" />
        </div>
        <div class="col-12 col-xl-auto d-flex justify-content-xl-end">
            <a class="btn-primary" href="{{ route('admin.dashboard.iam.users.create-engineer') }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-another-document-clas"></use></svg>
                Add User
            </a>
        </div>
    </div>

    <div class="bulk-bar" id="bulkBar">
        <span class="bulk-count" id="bulkCount">0 users selected</span>
        <div class="bulk-actions">
            <button class="bulk-btn approve" type="button">Approve Verification</button>
            <button class="bulk-btn suspend" type="button">Suspend</button>
            <button class="bulk-btn export" type="button">Export Selected</button>
        </div>
        <button type="button" data-clear-selection>Clear</button>
    </div>

    <div class="table-wrap">
        <form id="engineerFilterForm" method="GET" action="{{ route('admin.dashboard.iam.users.engineers') }}">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
        </form>
        <div class="table-header">
            <div>
                <select class="filter-select" id="bulkActionSelect">
                    <option value="">Bulk Actions</option>
                    <option value="verify">Verify</option>
                    <option value="suspend">Suspend</option>
                    <option value="delete">Delete</option>
                </select>
            </div>
            <div>
                <button class="btn-apply" type="button" data-bulk-apply>Apply</button>
            </div>

            <div class="search-form">
                <div class="search-box">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-search-2"></use></svg>
                    <input form="engineerFilterForm" type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Search users...">
                </div>
                <select form="engineerFilterForm" class="filter-select js-auto-submit" name="account_type">
                    <option value="all" @selected(($filters['account_type'] ?? 'all') === 'all')>All Account Types</option>
                    <option value="registered" @selected(($filters['account_type'] ?? 'all') === 'registered')>Registered Member</option>
                    <option value="professional" @selected(($filters['account_type'] ?? 'all') === 'professional')>Industry Professional</option>
                </select>
                <select form="engineerFilterForm" class="filter-select js-auto-submit" name="status">
                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All Statuses</option>
                    <option value="active" @selected(($filters['status'] ?? 'all') === 'active')>Active</option>
                    <option value="pending" @selected(($filters['status'] ?? 'all') === 'pending')>Pending Verification</option>
                    <option value="suspended" @selected(($filters['status'] ?? 'all') === 'suspended')>Suspended</option>
                    <option value="frozen" @selected(($filters['status'] ?? 'all') === 'frozen')>Frozen</option>
                </select>
                <select form="engineerFilterForm" class="filter-select js-auto-submit" name="plant_type_id">
                    <option value="">All Plants</option>
                    @foreach ($plantTypeOptions as $plantTypeId => $plantTypeName)
                        <option value="{{ $plantTypeId }}" @selected((string) ($filters['plant_type_id'] ?? '') === (string) $plantTypeId)>{{ $plantTypeName }}</option>
                    @endforeach
                </select>
                <button form="engineerFilterForm" class="btn-outline btn-filter" type="submit">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter"></use></svg>
                    Filter
                </button>
            </div>

            <div class="table-title-block">
                <div class="table-title">All Users</div>
                <div class="table-meta">Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} users</div>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
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
                        <tr>
                            <td><input class="row-check" type="checkbox"></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <img class="user-avatar-img" src="{{ $user->profile_photo_url ?: $engineerDefaultAvatar }}" alt="{{ $user->display_name }}">
                                    </div>
                                    <div>
                                        <a class="user-name" href="{{ route('admin.dashboard.iam.users.show', $user) }}">{{ $user->display_name }}</a>
                                        <div class="user-email">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge {{ $user->role_badge['class'] }}">
                                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-check"></use></svg>
                                    {{ $user->role_badge['label'] }}
                                </span>
                            </td>
                            <td><span class="exp-badge {{ $user->expertise_badge['class'] }}">{{ $user->expertise_badge['label'] }}</span></td>
                            <td>
                                <div class="points-cell">{{ number_format($user->contribution_points) }} <span>pts</span></div>
                                <div class="stars">
                                    @for ($i = 0; $i < 5; $i++)
                                        <svg class="star {{ $i < $user->filled_stars ? '' : 'empty' }}"><use href="/assets/icons/sprite.svg#icon-star"></use></svg>
                                    @endfor
                                </div>
                            </td>
                            <td>{{ $user->plant_type_label }}</td>
                            <td><span class="status-dot {{ $user->status_class }}">{{ $user->status_label }}</span></td>
                            <td>{{ $user->created_at?->format('d M Y') ?? '-' }}</td>
                            <td>
                                <div class="action-group">
                                    <a class="act-btn primary" title="View profile" href="{{ route('admin.dashboard.iam.users.show', $user) }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-partner-detail"></use></svg></a>
                                    <button class="act-btn" title="Send message" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-featured-answer-on-co-absorber"></use></svg></button>
                                    <button class="act-btn danger" title="Freeze account" type="button" data-freeze-open><svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg></button>
                                </div>
                            </td>
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
                <button class="page-btn" type="button" disabled><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-account-penalty"></use></svg></button>
            @else
                <a class="page-btn" href="{{ $users->previousPageUrl() }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-account-penalty"></use></svg></a>
            @endif
            @foreach ($users->getUrlRange(max(1, $users->currentPage() - 1), min($users->lastPage(), $users->currentPage() + 1)) as $page => $url)
                <a class="page-btn {{ $page === $users->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
            @endforeach
            @if ($users->currentPage() + 1 < $users->lastPage())
                <button class="page-btn" type="button" disabled>...</button>
                <a class="page-btn" href="{{ $users->url($users->lastPage()) }}">{{ $users->lastPage() }}</a>
            @endif
            @if ($users->hasMorePages())
                <a class="page-btn" href="{{ $users->nextPageUrl() }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg></a>
            @else
                <button class="page-btn" type="button" disabled><svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg></button>
            @endif
        </div>
    </div>

    <div class="drawer-overlay" id="drawerOverlay" data-drawer-close></div>
    <div class="drawer" id="detailDrawer">
        <div class="drawer-head">
            <div><div class="drawer-title" id="drawerName">User Details</div><div class="drawer-sub" id="drawerEmail">-</div></div>
            <button class="drawer-close" type="button" data-drawer-close><svg class="icon"><use href="/assets/icons/sprite.svg#icon-x"></use></svg></button>
        </div>
        <div class="drawer-body" id="drawerBody"><div class="profile-hero"><div class="profile-avatar-lg">U</div><div class="profile-hero-info"><div class="user-name">User Details</div><div class="user-email">Select a row action to review profile context.</div></div></div></div>
        <div class="drawer-footer" id="drawerFooter"><button class="drawer-act-btn success" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg>Approve Verification</button><button class="drawer-act-btn danger" type="button" data-freeze-open><svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg>Freeze Account</button><button class="drawer-act-btn" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-featured-answer-on-co-absorber"></use></svg>Message</button></div>
    </div>
    <div class="modal-overlay" id="freezeModal">
        <div class="modal"><div class="modal-title">Freeze this account?</div><div class="modal-desc">The user will immediately lose access to all platform features. They will receive an email notification. You can unfreeze the account at any time from Account Penalty &amp; Freeze.</div><textarea class="note-area" placeholder="Reason for freeze (optional, included in notification email)..."></textarea><div class="modal-actions"><button class="btn-cancel" type="button" data-freeze-close>Cancel</button><button class="btn-danger" type="button" data-freeze-confirm><svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg>Freeze Account</button></div></div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/engineer-management.js') }}"></script>
@endpush
