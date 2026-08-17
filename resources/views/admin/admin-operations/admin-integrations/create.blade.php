@extends('layouts.app')

@section('title', 'Connect Admin Integration')

@section('content_header')
    <div class="page-header"><div><div class="page-title">Connect Admin Integration</div><div class="page-subtitle">Register an admin email provider token for operational workflows.</div></div><div class="page-actions"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back to Operations</a></div></div>
@endsection

@section('content')
    @include('templates.components.alert-session')
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('admin.dashboard.admin-operations.admin-integrations.store') }}" class="form-card">
        @csrf
        <div class="form-card-header"><div class="form-card-icon emerald"><i class="bi bi-plug"></i></div><div><div class="form-card-title">OAuth Token</div><div class="form-card-sub">Ready static integration form using the existing controller contract.</div></div></div>
        <div class="form-card-body"><div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="user_id">Admin user</label><select id="user_id" class="form-select" name="user_id" required>@foreach ($adminUsers as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->email }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="provider">Provider</label><select id="provider" class="form-select" name="provider" required><option value="gmail" @selected(old('provider') === 'gmail')>Gmail</option><option value="outlook" @selected(old('provider') === 'outlook')>Outlook</option></select></div>
            <div class="col-md-4"><label class="form-label" for="token_expires_at">Expires at</label><input id="token_expires_at" type="datetime-local" class="form-control" name="token_expires_at" value="{{ old('token_expires_at') }}" required></div>
            <div class="col-12"><label class="form-label" for="access_token">Access token</label><textarea id="access_token" class="form-control" name="access_token" rows="3" required>{{ old('access_token') }}</textarea></div>
            <div class="col-12"><label class="form-label" for="refresh_token">Refresh token</label><textarea id="refresh_token" class="form-control" name="refresh_token" rows="3">{{ old('refresh_token') }}</textarea></div>
        </div></div>
        <div class="page-actions" style="justify-content:flex-end;"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn">Cancel</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Save Integration</button></div>
    </form>
@endsection
