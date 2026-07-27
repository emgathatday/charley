@extends('layouts.app')

@section('title', 'Create Account Penalty')

@section('content_header')
    <div class="page-header"><div><div class="page-title">Create Account Penalty</div><div class="page-subtitle">Apply a warning, suspension, freeze, or access restoration to a user account.</div></div><div class="page-actions"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back to Operations</a></div></div>
@endsection

@section('content')
    @include('templates.components.alert-session')
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('admin.dashboard.admin-operations.account-penalties.store') }}" class="form-card">
        @csrf
        <div class="form-card-header"><div class="form-card-icon amber"><i class="bi bi-shield-exclamation"></i></div><div><div class="form-card-title">Penalty Details</div><div class="form-card-sub">Recorded in account history and enforced by admin operations.</div></div></div>
        <div class="form-card-body"><div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="user_id">User</label><select id="user_id" class="form-select" name="user_id" required>@foreach ($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->email }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="action_type">Action</label><select id="action_type" class="form-select" name="action_type" required>@foreach (['warning','temporary_suspension','account_freeze','unfreeze','ban','self_freeze','self_unfreeze'] as $action)<option value="{{ $action }}" @selected(old('action_type') === $action)>{{ str_replace('_', ' ', $action) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="duration_days">Duration days</label><input id="duration_days" type="number" min="1" class="form-control" name="duration_days" value="{{ old('duration_days') }}" placeholder="Optional"></div>
            <div class="col-md-6"><label class="form-label" for="starts_at">Starts at</label><input id="starts_at" type="datetime-local" class="form-control" name="starts_at" value="{{ old('starts_at') }}"></div>
            <div class="col-md-6"><label class="form-label" for="ends_at">Ends at</label><input id="ends_at" type="datetime-local" class="form-control" name="ends_at" value="{{ old('ends_at') }}"></div>
            <div class="col-12"><label class="form-label" for="reason">Reason</label><textarea id="reason" class="form-control" name="reason" rows="5" required>{{ old('reason') }}</textarea></div>
        </div></div>
        <div class="page-actions" style="justify-content:flex-end;"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn">Cancel</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Save Penalty</button></div>
    </form>
@endsection
