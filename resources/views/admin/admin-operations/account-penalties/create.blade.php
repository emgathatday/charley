@extends('layouts.master')

@section('title', 'Create Account Penalty')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Create Account Penalty</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.admin-operations.index') }}">Operations</a></li><li class="breadcrumb-item active">Penalty</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="card card-outline card-danger">
            <div class="card-header"><h3 class="card-title mb-0">Penalty details</h3></div>
            <form method="POST" action="{{ route('admin.dashboard.admin-operations.account-penalties.store') }}">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">User</label><select class="form-select" name="user_id">@foreach ($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->email }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Action</label><select class="form-select" name="action_type"><option>warning</option><option>temporary_suspension</option><option>account_freeze</option><option>unfreeze</option><option>ban</option></select></div>
                        <div class="col-md-4"><label class="form-label">Duration days</label><input type="number" class="form-control" name="duration_days" placeholder="Optional"></div>
                        <div class="col-md-6"><label class="form-label">Starts at</label><input type="datetime-local" class="form-control" name="starts_at"></div>
                        <div class="col-md-6"><label class="form-label">Ends at</label><input type="datetime-local" class="form-control" name="ends_at"></div>
                        <div class="col-12"><label class="form-label">Reason</label><textarea class="form-control" name="reason" rows="5"></textarea></div>
                    </div>
                </div>
                <div class="card-footer text-end"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-danger" type="submit">Save Draft</button></div>
            </form>
        </div>
    </div></div>
@endsection


