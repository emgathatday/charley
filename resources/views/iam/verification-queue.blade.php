@extends('layouts.app')

@section('title', 'Verification Queue')

@section('content_header')
    <div class="page-header">
        <div>
            <div class="page-title">Verification Queue</div>
            <div class="page-subtitle">Review identity submissions by status, method, applicant, and reviewer state.</div>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('admin.dashboard.iam.users') }}"><i class="bi bi-people" aria-hidden="true"></i>User Management</a>
        </div>
    </div>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="stats-row" style="grid-template-columns:repeat(4,minmax(0,1fr));padding:0;margin-bottom:22px;">
        <a class="stat-card amber" href="{{ route('admin.dashboard.iam.verification-queue', array_merge(request()->except('page'), ['status' => 'pending'])) }}"><div class="stat-label">Pending</div><div class="stat-value">{{ number_format($queueStats['pending']) }}</div></a>
        <a class="stat-card blue" href="{{ route('admin.dashboard.iam.verification-queue', array_merge(request()->except('page'), ['status' => 'more_info_required'])) }}"><div class="stat-label">More Info</div><div class="stat-value">{{ number_format($queueStats['more_info_required']) }}</div></a>
        <a class="stat-card emerald" href="{{ route('admin.dashboard.iam.verification-queue', array_merge(request()->except('page'), ['status' => 'approved'])) }}"><div class="stat-label">Approved</div><div class="stat-value">{{ number_format($queueStats['approved']) }}</div></a>
        <a class="stat-card red" href="{{ route('admin.dashboard.iam.verification-queue', array_merge(request()->except('page'), ['status' => 'rejected'])) }}"><div class="stat-label">Rejected</div><div class="stat-value">{{ number_format($queueStats['rejected']) }}</div></a>
    </div>

    <form class="filter-bar" method="GET" action="{{ route('admin.dashboard.iam.verification-queue') }}">
        <div class="filter-search">
            <i class="bi bi-search"></i>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, username or email">
        </div>
        <select class="filter-select" id="status" name="status">
            <option value="">All statuses</option>
            @foreach (['pending', 'approved', 'rejected', 'more_info_required'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>
            @endforeach
        </select>
        <select class="filter-select" id="method" name="method">
            <option value="">All methods</option>
            @foreach (['work_email', 'linkedin', 'company_letter', 'university_letter', 'justification_letter'] as $method)
                <option value="{{ $method }}" @selected(($filters['method'] ?? '') === $method)>{{ str_replace('_', ' ', $method) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Apply</button>
    </form>

    <div class="table-card">
        <div class="table-header"><div><div class="table-title">Identity Review Items</div><div class="table-meta">{{ $verificationRequests->total() }} results</div></div></div>
        <div class="table-responsive">
            <table class="qa-table">
                <thead><tr><th>Applicant</th><th>Method</th><th>Type</th><th>Status</th><th>Submitted</th><th>Reviewed By</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($verificationRequests as $request)
                        <tr>
                            <td><a class="fw-semibold text-decoration-none" href="{{ route('admin.dashboard.iam.user-security', $request->user) }}">{{ trim($request->user->first_name . ' ' . $request->user->last_name) ?: $request->user->username ?: $request->user->email }}</a><div class="small text-secondary">{{ $request->user->email }}</div></td>
                            <td><span class="badge badge-info">{{ str_replace('_', ' ', $request->verification_method) }}</span></td>
                            <td>{{ str_replace('_', ' ', $request->submission_type) }}</td>
                            <td><span class="badge {{ $request->status === 'approved' ? 'badge-success' : ($request->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ str_replace('_', ' ', $request->status) }}</span></td>
                            <td>{{ $request->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $request->reviewer?->email ?? '-' }}</td>
                            <td class="text-end"><div class="d-flex justify-content-end gap-2">
                                <form method="POST" action="{{ route('admin.dashboard.iam.verification-queue.approve', $request) }}">@csrf<input type="hidden" name="admin_notes" value="Approved from admin dashboard"><button class="btn" type="submit" title="Approve"><i class="bi bi-check-lg"></i></button></form>
                                <form method="POST" action="{{ route('admin.dashboard.iam.verification-queue.more-info', $request) }}">@csrf<input type="hidden" name="admin_notes" value="Please provide additional verification evidence."><button class="btn" type="submit" title="More info"><i class="bi bi-chat-left-text"></i></button></form>
                                <form method="POST" action="{{ route('admin.dashboard.iam.verification-queue.reject', $request) }}">@csrf<input type="hidden" name="admin_notes" value="Rejected from admin dashboard review."><button class="btn" type="submit" title="Reject"><i class="bi bi-x-lg"></i></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No verification requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-foot"><span class="table-foot-info">Showing {{ $verificationRequests->firstItem() ?? 0 }}-{{ $verificationRequests->lastItem() ?? 0 }} of {{ number_format($verificationRequests->total()) }}</span>{{ $verificationRequests->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
