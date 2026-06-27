@extends('layouts.master')

@section('title', 'Verification Queue')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row"><div class="col-sm-6"><h3 class="mb-0">Verification Queue</h3></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item active">Verification Queue</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="row">
                <div class="col-lg-3 col-6"><div class="small-box text-bg-warning"><div class="inner"><h3>{{ number_format($queueStats['pending']) }}</h3><p>Pending</p></div><i class="small-box-icon bi bi-hourglass-split"></i></div></div>
                <div class="col-lg-3 col-6"><div class="small-box text-bg-info"><div class="inner"><h3>{{ number_format($queueStats['more_info_required']) }}</h3><p>More info</p></div><i class="small-box-icon bi bi-chat-left-text"></i></div></div>
                <div class="col-lg-3 col-6"><div class="small-box text-bg-success"><div class="inner"><h3>{{ number_format($queueStats['approved']) }}</h3><p>Approved</p></div><i class="small-box-icon bi bi-check2-circle"></i></div></div>
                <div class="col-lg-3 col-6"><div class="small-box text-bg-danger"><div class="inner"><h3>{{ number_format($queueStats['rejected']) }}</h3><p>Rejected</p></div><i class="small-box-icon bi bi-x-circle"></i></div></div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Filters</h3></div>
                <form class="card-body" method="GET" action="{{ route('admin.dashboard.iam.verification-queue') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4"><label class="form-label" for="search">Applicant</label><input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, username or email"></div>
                        <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All</option>@foreach (['pending', 'approved', 'rejected', 'more_info_required'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label" for="method">Method</label><select class="form-select" id="method" name="method"><option value="">All</option>@foreach (['work_email', 'linkedin', 'company_letter', 'university_letter', 'justification_letter'] as $method)<option value="{{ $method }}" @selected(($filters['method'] ?? '') === $method)>{{ $method }}</option>@endforeach</select></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Apply</button></div>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Identity review items</h3><div class="card-tools"><span class="badge text-bg-secondary">{{ $verificationRequests->total() }} results</span></div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Applicant</th><th>Method</th><th>Type</th><th>Status</th><th>Submitted</th><th>Reviewed by</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                @forelse ($verificationRequests as $request)
                                    <tr>
                                        <td><a href="{{ route('admin.dashboard.iam.user-security', $request->user) }}" class="fw-semibold text-decoration-none">{{ trim($request->user->first_name . ' ' . $request->user->last_name) ?: $request->user->username ?: $request->user->email }}</a><div class="small text-secondary">{{ $request->user->email }}</div></td>
                                        <td><span class="badge text-bg-info">{{ $request->verification_method }}</span></td>
                                        <td>{{ $request->submission_type }}</td>
                                        <td><span class="badge {{ $request->status === 'approved' ? 'text-bg-success' : ($request->status === 'rejected' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ $request->status }}</span></td>
                                        <td class="text-nowrap">{{ $request->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>{{ $request->reviewer?->email ?? '-' }}</td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                                <form method="POST" action="{{ route('admin.dashboard.iam.verification-queue.approve', $request) }}">@csrf<input type="hidden" name="admin_notes" value="Approved from admin dashboard"><button class="btn btn-sm btn-outline-success" type="submit" title="Approve"><i class="bi bi-check-lg"></i></button></form>
                                                <form method="POST" action="{{ route('admin.dashboard.iam.verification-queue.more-info', $request) }}">@csrf<input type="hidden" name="admin_notes" value="Please provide additional verification evidence."><button class="btn btn-sm btn-outline-warning" type="submit" title="More info"><i class="bi bi-chat-left-text"></i></button></form>
                                                <form method="POST" action="{{ route('admin.dashboard.iam.verification-queue.reject', $request) }}">@csrf<input type="hidden" name="admin_notes" value="Rejected from admin dashboard review."><button class="btn btn-sm btn-outline-danger" type="submit" title="Reject"><i class="bi bi-x-lg"></i></button></form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-4">No verification requests found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">{{ $verificationRequests->links() }}</div>
            </div>
        </div>
    </div>
@endsection
