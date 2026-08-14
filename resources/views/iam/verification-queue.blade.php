@extends('layouts.rebuild-dashboard')

@section('title', 'Profile Verification Queue')

@php
    $activeStatus = $filters['status'] ?? '';
    $activeMethod = $filters['method'] ?? '';
    $searchValue = $filters['search'] ?? '';
    $statusTabs = [
        '' => ['label' => 'All', 'count' => $queueStats['all'] ?? $verificationRequests->total()],
        'pending' => ['label' => 'Pending', 'count' => $queueStats['pending'] ?? 0],
        'more_info_required' => ['label' => 'Info Requested', 'count' => $queueStats['more_info_required'] ?? 0],
        'approved' => ['label' => 'Approved', 'count' => $queueStats['approved'] ?? 0],
        'rejected' => ['label' => 'Rejected', 'count' => $queueStats['rejected'] ?? 0],
    ];
    $statusSummary = [
        ['class' => 'amber', 'label' => 'Pending Review', 'value' => $queueStats['pending'] ?? 0, 'sub' => 'Awaiting admin decision', 'icon' => 'icon-14-pending-review-approval'],
        ['class' => 'blue', 'label' => 'Info Requested', 'value' => $queueStats['more_info_required'] ?? 0, 'sub' => 'Waiting on applicant', 'icon' => 'icon-questions-attached'],
        ['class' => 'indigo', 'label' => 'Approved', 'value' => $queueStats['approved'] ?? 0, 'sub' => 'Completed applications', 'icon' => 'icon-check-2'],
        ['class' => '', 'label' => 'Rejected', 'value' => $queueStats['rejected'] ?? 0, 'sub' => 'Closed after review', 'icon' => 'icon-reject-application-internal-revi'],
    ];
    $verificationStatCards = collect($statusSummary)->map(fn ($summary) => [
        'class' => $summary['class'],
        'label' => $summary['label'],
        'value' => number_format($summary['value']),
        'sub' => $summary['sub'],
        'chip' => ['class' => $summary['class'] === '' ? 'red' : 'up', 'icon' => $summary['icon'], 'label' => number_format($summary['value']) . ' applications'],
    ])->all();
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Profile Verification Queue</h1>
            <p>Review credentials, company documents, and certifications submitted by professionals and partner organizations applying for verified status on Charley.</p>
        </div>
        <div class="page-actions">
            <a class="btn-outline" href="{{ route('admin.dashboard.iam.users') }}">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-admin-actions"></use></svg>
                User Management
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Review note required.</strong>
            <div>{{ $errors->first('admin_notes') ?: $errors->first() }}</div>
        </div>
    @endif

    {{ \App\Support\AdminStatCards::render($verificationStatCards) }}

    <div class="filter-bar">
        <div class="tab-group">
            @foreach ($statusTabs as $status => $tab)
                <a class="tab-item {{ $activeStatus === $status ? 'active' : '' }}" href="{{ route('admin.dashboard.iam.verification-queue', array_filter(array_merge(request()->except('page'), ['status' => $status]), fn ($value) => $value !== '' && $value !== null)) }}">
                    {{ $tab['label'] }} <span class="tab-count">{{ number_format($tab['count']) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <div>
                <select class="filter-select" id="bulkSelect">
                    <option value="">Bulk Actions</option>
                    <option value="approve">Approve selected</option>
                    <option value="more_info_required">Request more info</option>
                    <option value="reject">Reject selected</option>
                </select>
            </div>
            <div>
                <button class="btn-apply" type="button">Apply</button>
            </div>

            <form class="search-form" method="GET" action="{{ route('admin.dashboard.iam.verification-queue') }}">
                <div class="search-box">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-search-2"></use></svg>
                    <input type="text" name="search" value="{{ $searchValue }}" placeholder="Search applicants...">
                </div>
                <select class="filter-select" name="method" onchange="this.form.submit()">
                    <option value="">All Methods</option>
                    @foreach ($methodOptions as $method)
                        <option value="{{ $method }}" @selected($activeMethod === $method)>{{ str_replace('_', ' ', ucwords($method, '_')) }}</option>
                    @endforeach
                </select>
                <select class="filter-select" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($activeStatus === $status)>{{ str_replace('_', ' ', ucwords($status, '_')) }}</option>
                    @endforeach
                </select>
                <button class="btn-outline btn-filter" type="submit">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter"></use></svg>
                    Filter
                </button>
            </form>

            <div class="table-title-block">
                <div class="table-title">All Applications</div>
                <div class="table-meta">Showing {{ $verificationRequests->firstItem() ?? 0 }}-{{ $verificationRequests->lastItem() ?? 0 }} of {{ number_format($verificationRequests->total()) }} applications</div>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="cb" id="selectAllCb" onchange="toggleSelectAll(this)"></th>
                        <th>Applicant</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Documents</th>
                        <th>Submitted</th>
                        <th>SLA</th>
                        <th>Status</th>
                        <th>Reviewer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($verificationRequests as $verificationRequest)
                        @php
                            $documents = $verificationRequest->document_media ?? [];
                            $firstDocument = $documents[0] ?? null;
                        @endphp
                        <tr>
                            <td onclick="event.stopPropagation()"><input type="checkbox" class="cb row-cb"></td>
                            <td>
                                <div class="applicant-cell">
                                    <div class="applicant-avatar {{ ($verificationRequest->user?->role ?? '') === 'partner' ? 'logo' : '' }}">
                                        {{ strtoupper(substr($verificationRequest->applicant_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a class="applicant-name" href="{{ route('admin.dashboard.iam.verification-queue.show', $verificationRequest) }}">{{ $verificationRequest->applicant_name }}</a>
                                        <div class="applicant-meta">{{ $verificationRequest->user?->email ?? 'No email' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="type-tag {{ $verificationRequest->applicant_type_class }}">{{ $verificationRequest->applicant_type_label }}</span></td>
                            <td>{{ $verificationRequest->method_label }}<div class="sla-sub">{{ $verificationRequest->submission_type_label }}</div></td>
                            <td>
                                <div>{{ count($documents) }} {{ count($documents) === 1 ? 'file' : 'files' }}</div>
                                @if ($firstDocument)
                                    <div class="sla-sub">
                                        @if ($firstDocument['url'])
                                            <a href="{{ $firstDocument['url'] }}" target="_blank" rel="noopener">{{ $firstDocument['name'] }}</a>
                                        @else
                                            {{ $firstDocument['name'] }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>{{ $verificationRequest->created_at?->format('M j, Y') }}<div class="sla-sub">{{ $verificationRequest->created_at?->format('h:i A') }}</div></td>
                            <td><div class="sla {{ $verificationRequest->sla['class'] }}">{{ $verificationRequest->sla['label'] }}</div><div class="sla-sub">{{ $verificationRequest->sla['sub'] }}</div></td>
                            <td><span class="badge {{ $verificationRequest->status_class }}"><span class="dot-sm"></span>{{ $verificationRequest->status_label }}</span></td>
                            <td>{{ $verificationRequest->reviewer_name ?? '-' }}<div class="sla-sub">{{ $verificationRequest->reviewed_at?->format('M j, Y') ?? 'Not reviewed' }}</div></td>
                            <td onclick="event.stopPropagation()">
                                <div class="row-actions">
                                    <a class="row-btn view" title="View verification detail" href="{{ route('admin.dashboard.iam.verification-queue.show', $verificationRequest) }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-input-type-checkbox-id"></use></svg></a>
                                    <button class="row-btn" type="button" title="Approve" onclick="openReviewPanel('approve-{{ $verificationRequest->id }}')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-check-2"></use></svg></button>
                                    <button class="row-btn" type="button" title="More info" onclick="openReviewPanel('more-{{ $verificationRequest->id }}')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-questions-attached"></use></svg></button>
                                    <button class="row-btn" type="button" title="Reject" onclick="openReviewPanel('reject-{{ $verificationRequest->id }}')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-reject-application-internal-revi"></use></svg></button>
                                </div>
                                <div class="review-panel" id="approve-{{ $verificationRequest->id }}" hidden>
                                    <form method="POST" action="{{ route('admin.dashboard.iam.verification-queue.approve', $verificationRequest) }}">
                                        @csrf
                                        <textarea class="note-area" name="admin_notes" placeholder="Approval note (optional)">{{ old('admin_notes') }}</textarea>
                                        <button class="btn-primary" type="submit">Approve</button>
                                    </form>
                                </div>
                                <div class="review-panel" id="more-{{ $verificationRequest->id }}" hidden>
                                    <form method="POST" action="{{ route('admin.dashboard.iam.verification-queue.more-info', $verificationRequest) }}">
                                        @csrf
                                        <textarea class="note-area @error('admin_notes') is-invalid @enderror" name="admin_notes" placeholder="Required note for applicant">{{ old('admin_notes') }}</textarea>
                                        @error('admin_notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        <button class="btn-outline" type="submit">Request More Info</button>
                                    </form>
                                </div>
                                <div class="review-panel" id="reject-{{ $verificationRequest->id }}" hidden>
                                    <form method="POST" action="{{ route('admin.dashboard.iam.verification-queue.reject', $verificationRequest) }}">
                                        @csrf
                                        <textarea class="note-area @error('admin_notes') is-invalid @enderror" name="admin_notes" placeholder="Required rejection note">{{ old('admin_notes') }}</textarea>
                                        @error('admin_notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        <button class="btn-danger" type="submit">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><div class="empty-state"><span>No verification applications match the selected filters.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-foot">
            <div class="foot-info">Showing <b>{{ $verificationRequests->firstItem() ?? 0 }}-{{ $verificationRequests->lastItem() ?? 0 }}</b> of <b>{{ number_format($verificationRequests->total()) }}</b> applications</div>
            <div class="pager">
                @if ($verificationRequests->onFirstPage())
                    <button class="pager-btn" type="button" disabled><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-account-penalty"></use></svg></button>
                @else
                    <a class="pager-btn" href="{{ $verificationRequests->previousPageUrl() }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-account-penalty"></use></svg></a>
                @endif
                @foreach ($verificationRequests->getUrlRange(max(1, $verificationRequests->currentPage() - 1), min($verificationRequests->lastPage(), $verificationRequests->currentPage() + 1)) as $page => $url)
                    <a class="pager-btn {{ $page === $verificationRequests->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                @endforeach
                @if ($verificationRequests->hasMorePages())
                    <a class="pager-btn" href="{{ $verificationRequests->nextPageUrl() }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg></a>
                @else
                    <button class="pager-btn" type="button" disabled><svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg></button>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function toggleSelectAll(cb){document.querySelectorAll('.row-cb').forEach((row)=>row.checked=cb.checked)}
function openReviewPanel(id){document.querySelectorAll('.review-panel').forEach((panel)=>{if(panel.id!==id)panel.hidden=true});const panel=document.getElementById(id);if(panel)panel.hidden=!panel.hidden}
</script>
@endpush

