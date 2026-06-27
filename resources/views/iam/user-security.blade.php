@extends('layouts.master')

@section('title', 'User Security')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row"><div class="col-sm-6"><h3 class="mb-0">User Security</h3></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item active">{{ $user->email }}</li></ol></div></div></div></div>
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
                <div class="col-md-6 col-xl-3"><div class="card mb-3"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-shrink-0"><i class="bi bi-person-badge fs-1 text-primary"></i></div><div class="flex-grow-1 ms-3"><div class="text-secondary">Role</div><h4 class="mb-0">{{ $user->role }}</h4></div></div></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card mb-3"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-shrink-0"><i class="bi bi-shield-check fs-1 text-success"></i></div><div class="flex-grow-1 ms-3"><div class="text-secondary">Account status</div><h4 class="mb-0">{{ $user->status }}</h4></div></div></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card mb-3"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-shrink-0"><i class="bi bi-key fs-1 text-info"></i></div><div class="flex-grow-1 ms-3"><div class="text-secondary">MFA</div><h4 class="mb-0">{{ $user->mfa_enabled ? 'enabled' : 'disabled' }}</h4></div></div></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card mb-3"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-shrink-0"><i class="bi bi-patch-check fs-1 text-warning"></i></div><div class="flex-grow-1 ms-3"><div class="text-secondary">Verification</div><h4 class="mb-0">{{ $user->verification_expires_at ? $user->verification_expires_at->diffForHumans() : 'not set' }}</h4></div></div></div></div></div>
            </div>

            <div class="row">
                <div class="col-lg-5">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Account controls</h3></div>
                        <form class="card-body" method="POST" action="{{ route('admin.dashboard.iam.user-security.update', $user) }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-3"><label class="form-label" for="role">Role</label><select class="form-select" id="role" name="role">@foreach (['professional', 'unverified_member', 'partner', 'admin'] as $role)<option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ $role }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label" for="status">Account status</label><select class="form-select" id="status" name="status">@foreach (['active', 'suspended', 'frozen'] as $status)<option value="{{ $status }}" @selected(old('status', $user->status) === $status)>{{ $status }}</option>@endforeach</select></div>
                            <div class="mb-3"><label for="admin_note" class="form-label">Admin note</label><input type="text" class="form-control" id="admin_note" name="admin_note" value="{{ old('admin_note') }}" placeholder="Reason for account status change"></div>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save controls</button>
                        </form>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Identity</h3></div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ trim($user->first_name . ' ' . $user->last_name) ?: $user->username ?: '-' }}</dd>
                                <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $user->email }}</dd>
                                <dt class="col-sm-4">Verified at</dt><dd class="col-sm-8">{{ $user->verified_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                                <dt class="col-sm-4">Latest request</dt><dd class="col-sm-8">{{ $latestVerification?->status ?? '-' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Security state</h3></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead><tr><th>Signal</th><th>State</th><th>Observed at</th></tr></thead>
                                    <tbody>
                                        <tr><td>Login attempts</td><td><span class="badge {{ $user->login_attempts > 0 ? 'text-bg-warning' : 'text-bg-success' }}">{{ $user->login_attempts }} failed</span></td><td>{{ $user->updated_at?->format('Y-m-d H:i') }}</td></tr>
                                        <tr><td>Last login</td><td><span class="badge text-bg-info">{{ $user->last_login_at ? 'successful' : 'never' }}</span></td><td>{{ $user->last_login_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                        <tr><td>Locked until</td><td><span class="badge {{ $user->locked_until ? 'text-bg-danger' : 'text-bg-success' }}">{{ $user->locked_until ? 'locked' : 'clear' }}</span></td><td>{{ $user->locked_until?->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                        <tr><td>Self freeze</td><td><span class="badge {{ $user->self_frozen_at ? 'text-bg-secondary' : 'text-bg-success' }}">{{ $user->self_frozen_at ? 'active' : 'not active' }}</span></td><td>{{ $user->self_frozen_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Recent verification requests</h3></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead><tr><th>Type</th><th>Method</th><th>Status</th><th>Submitted</th></tr></thead>
                                    <tbody>
                                        @forelse ($user->verificationRequests as $request)
                                            <tr><td>{{ $request->submission_type }}</td><td>{{ $request->verification_method }}</td><td><span class="badge text-bg-info">{{ $request->status }}</span></td><td>{{ $request->created_at?->format('Y-m-d H:i') }}</td></tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-secondary py-4">No verification history.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

