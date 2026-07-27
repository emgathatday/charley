@extends('layouts.app')

@section('title', 'User Security')

@section('content_header')
    <div class="page-header">
        <div>
            <div class="page-title">User Security</div>
            <div class="page-subtitle">{{ $user->email }} - account controls, login signals, and latest verification history.</div>
        </div>
        <div class="page-actions"><a class="btn" href="{{ route('admin.dashboard.iam.users') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back to IAM</a></div>
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
        <div class="stat-card blue"><div class="stat-label">Role</div><div class="stat-value">{{ str_replace('_', ' ', $user->role) }}</div></div>
        <div class="stat-card emerald"><div class="stat-label">Account Status</div><div class="stat-value">{{ $user->status }}</div></div>
        <div class="stat-card indigo"><div class="stat-label">MFA</div><div class="stat-value">{{ $user->mfa_enabled ? 'enabled' : 'disabled' }}</div></div>
        <div class="stat-card amber"><div class="stat-label">Verification</div><div class="stat-value">{{ $user->verification_expires_at ? $user->verification_expires_at->diffForHumans() : 'not set' }}</div></div>
    </div>

    <div class="detail-grid">
        <section class="form-card">
            <div class="form-card-header"><div class="form-card-icon blue"><i class="bi bi-shield-lock"></i></div><div><div class="form-card-title">Account Controls</div><div class="form-card-sub">Role and account state changes</div></div></div>
            <form class="form-card-body" method="POST" action="{{ route('admin.dashboard.iam.user-security.update', $user) }}">
                @csrf
                @method('PUT')
                <div class="mb-3"><label class="form-label" for="role">Role</label><select class="form-select" id="role" name="role">@foreach (['professional', 'unverified_member', 'partner', 'admin'] as $role)<option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ str_replace('_', ' ', $role) }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label" for="status">Account status</label><select class="form-select" id="status" name="status">@foreach (['active', 'suspended', 'frozen'] as $status)<option value="{{ $status }}" @selected(old('status', $user->status) === $status)>{{ $status }}</option>@endforeach</select></div>
                <div class="mb-3"><label for="admin_note" class="form-label">Admin note</label><input type="text" class="form-control" id="admin_note" name="admin_note" value="{{ old('admin_note') }}" placeholder="Reason for account status change"></div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-save" aria-hidden="true"></i>Save Controls</button>
            </form>
        </section>

        <section class="form-card">
            <div class="form-card-header"><div class="form-card-icon indigo"><i class="bi bi-person-vcard"></i></div><div><div class="form-card-title">Identity</div><div class="form-card-sub">Linked account identity and verification state</div></div></div>
            <div class="form-card-body"><div class="info-grid">
                <div class="info-item"><div class="info-label">Name</div><div class="info-value">{{ trim($user->first_name . ' ' . $user->last_name) ?: $user->username ?: '-' }}</div></div>
                <div class="info-item"><div class="info-label">Email</div><div class="info-value"><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></div></div>
                <div class="info-item"><div class="info-label">Verified At</div><div class="info-value">{{ $user->verified_at?->format('Y-m-d H:i') ?? '-' }}</div></div>
                <div class="info-item"><div class="info-label">Latest Request</div><div class="info-value">{{ $latestVerification?->status ?? '-' }}</div></div>
            </div></div>
        </section>
    </div>

    <div class="table-card">
        <div class="table-header"><div><div class="table-title">Security State</div><div class="table-meta">Current login and lock signals</div></div></div>
        <div class="table-responsive"><table class="qa-table"><thead><tr><th>Signal</th><th>State</th><th>Observed At</th></tr></thead><tbody>
            <tr><td>Login attempts</td><td><span class="badge {{ $user->login_attempts > 0 ? 'badge-warning' : 'badge-success' }}">{{ $user->login_attempts }} failed</span></td><td>{{ $user->updated_at?->format('Y-m-d H:i') }}</td></tr>
            <tr><td>Last login</td><td><span class="badge badge-info">{{ $user->last_login_at ? 'successful' : 'never' }}</span></td><td>{{ $user->last_login_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
            <tr><td>Locked until</td><td><span class="badge {{ $user->locked_until ? 'badge-danger' : 'badge-success' }}">{{ $user->locked_until ? 'locked' : 'clear' }}</span></td><td>{{ $user->locked_until?->format('Y-m-d H:i') ?? '-' }}</td></tr>
            <tr><td>Self freeze</td><td><span class="badge {{ $user->self_frozen_at ? 'badge-warning' : 'badge-success' }}">{{ $user->self_frozen_at ? 'active' : 'not active' }}</span></td><td>{{ $user->self_frozen_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
        </tbody></table></div>
    </div>
@endsection
