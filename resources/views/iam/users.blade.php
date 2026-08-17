@extends('layouts.rebuild-dashboard')

@section('title', 'Administrator Management')

@section('content')
    <div class="page-head">
        <div>
            <h1>Administrator Management</h1>
            <p>Manage internal admin and moderator accounts, review security posture, and monitor operator access.</p>
        </div>
    </div>

    <x-admin.stat-cards :items="$adminStatCards" />

    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            <x-admin.tab-bar :items="$adminTabBar" />
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
        <div class="bulk-actions">
            <button class="bulk-btn suspend" type="button">Suspend</button>
            <button class="bulk-btn export" type="button">Export Selected</button>
        </div>
        <button type="button" data-clear-selection>Clear</button>
    </div>

    <div class="table-wrap">
        <form id="adminFilterForm" method="GET" action="{{ route('admin.dashboard.iam.users') }}">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
        </form>
        <div class="table-header">
            <div>
                <select class="filter-select" id="bulkActionSelect">
                    <option value="">Bulk Actions</option>
                    <option value="suspend">Suspend</option>
                    <option value="freeze">Freeze</option>
                    <option value="export">Export</option>
                </select>
            </div>
            <div><button class="btn-apply" type="button" data-bulk-apply>Apply</button></div>
            <div class="search-form">
                <div class="search-box">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-k-overview-a-href-admin-d"></use></svg>
                    <input form="adminFilterForm" type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Search administrators...">
                </div>
                <select form="adminFilterForm" class="filter-select js-auto-submit" name="status">
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
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Operator</th>
                        <th>Account Type</th>
                        <th>Security</th>
                        <th>Last Login</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    @forelse ($users as $user)
                        <tr>
                            <td><input class="row-check" type="checkbox"></td>
                            <td><div class="user-cell"><div class="user-avatar"><div class="avatar">{{ $user->initials }}</div></div><div><a class="user-name" href="{{ route('admin.dashboard.iam.users.show', $user) }}">{{ $user->display_name }}</a><div class="user-email">{{ $user->email }}</div></div></div></td>
                            <td><span class="role-badge {{ $user->role_badge['class'] }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-check"></use></svg>{{ $user->role_badge['label'] }}</span></td>
                            <td><span class="exp-badge {{ $user->security_class }}">{{ $user->security_label }}</span></td>
                            <td>{{ $user->last_login_label }}</td>
                            <td>{{ $user->role_label }}</td>
                            <td><span class="status-dot {{ $user->status_class }}">{{ $user->status_label }}</span></td>
                            <td>{{ $user->created_at?->format('d M Y') ?? '-' }}</td>
                            <td><div class="action-group"><a class="act-btn primary" title="View profile" href="{{ route('admin.dashboard.iam.users.show', $user) }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-partner-detail"></use></svg></a><button class="act-btn danger" title="Freeze account" type="button" data-freeze-open><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg></button></div></td>
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

    <div class="modal-overlay" id="freezeModal"><div class="modal"><div class="modal-title">Freeze this account?</div><div class="modal-desc">The operator will immediately lose access to dashboard features. You can unfreeze the account from Account Penalty &amp; Freeze.</div><textarea class="note-area" placeholder="Reason for freeze..."></textarea><div class="modal-actions"><button class="btn-cancel" type="button" data-freeze-close>Cancel</button><button class="btn-danger" type="button" data-freeze-confirm>Freeze Account</button></div></div></div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/administrator-management.js') }}"></script>
@endpush
