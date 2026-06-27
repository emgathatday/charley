@extends('layouts.master')

@section('title', 'IAM Users')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">IAM Users</h3></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-end"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item active">Users</li></ol></div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="row">
                <div class="col-lg-3 col-6"><div class="small-box text-bg-primary"><div class="inner"><h3>{{ number_format($stats['total_users']) }}</h3><p>Total users</p></div><i class="small-box-icon bi bi-people"></i></div></div>
                <div class="col-lg-3 col-6"><div class="small-box text-bg-success"><div class="inner"><h3>{{ number_format($stats['verified_professionals']) }}</h3><p>Verified professionals</p></div><i class="small-box-icon bi bi-patch-check"></i></div></div>
                <div class="col-lg-3 col-6"><div class="small-box text-bg-warning"><div class="inner"><h3>{{ number_format($stats['pending_reviews']) }}</h3><p>Pending reviews</p></div><i class="small-box-icon bi bi-hourglass-split"></i></div></div>
                <div class="col-lg-3 col-6"><div class="small-box text-bg-danger"><div class="inner"><h3>{{ number_format($stats['security_flags']) }}</h3><p>Security flags</p></div><i class="small-box-icon bi bi-shield-exclamation"></i></div></div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Filters</h3></div>
                <form class="card-body" method="GET" action="{{ route('admin.dashboard.iam.users') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, username or email"></div>
                        <div class="col-md-2"><label class="form-label" for="role">Role</label><select class="form-select" id="role" name="role"><option value="">All</option>@foreach (['admin', 'unverified_member', 'professional', 'partner'] as $role)<option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All</option>@foreach (['active', 'suspended', 'frozen'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label" for="verified">Verification</label><select class="form-select" id="verified" name="verified"><option value="">All</option><option value="1" @selected(($filters['verified'] ?? '') === '1')>Verified</option><option value="0" @selected(($filters['verified'] ?? '') === '0')>Pending</option></select></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Apply</button></div>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">User directory</h3><div class="card-tools"><span class="badge text-bg-secondary">{{ $users->total() }} results</span></div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Verification</th><th>Requests</th><th>Last login</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td><a href="{{ route('admin.dashboard.iam.user-security', $user) }}" class="fw-semibold text-decoration-none">{{ trim($user->first_name . ' ' . $user->last_name) ?: $user->username ?: $user->email }}</a><div class="small text-secondary">{{ $user->email }}</div></td>
                                        <td><span class="badge text-bg-info">{{ $user->role }}</span></td>
                                        <td><span class="badge {{ $user->status === 'active' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $user->status }}</span></td>
                                        <td><span class="badge {{ $user->is_verified ? 'text-bg-success' : 'text-bg-warning' }}">{{ $user->is_verified ? 'Verified' : 'Pending' }}</span></td>
                                        <td>{{ $user->verification_requests_count }}</td>
                                        <td class="text-nowrap">{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                                        <td class="text-end"><div class="btn-group btn-group-sm"><a href="{{ route('admin.dashboard.iam.user-security', $user) }}" class="btn btn-outline-secondary" title="View security"><i class="bi bi-eye" aria-hidden="true"></i></a></div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-4">No users found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">{{ $users->links() }}</div>
            </div>
        </div>
    </div>
@endsection
