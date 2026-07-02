@extends('layouts.master')

@section('title', 'Connect Admin Integration')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Connect Admin Integration</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.admin-operations.index') }}">Operations</a></li><li class="breadcrumb-item active">Integration</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title mb-0">OAuth token</h3></div>
            <form method="POST" action="{{ route('admin.dashboard.admin-operations.admin-integrations.store') }}">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Admin user</label><select class="form-select" name="user_id">@foreach ($adminUsers as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->email }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Provider</label><select class="form-select" name="provider"><option>gmail</option><option>outlook</option></select></div>
                        <div class="col-md-4"><label class="form-label">Expires at</label><input type="datetime-local" class="form-control" name="token_expires_at"></div>
                        <div class="col-12"><label class="form-label">Access token</label><textarea class="form-control" name="access_token" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">Refresh token</label><textarea class="form-control" name="refresh_token" rows="3"></textarea></div>
                    </div>
                </div>
                <div class="card-footer text-end"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-success" type="submit">Save Draft</button></div>
            </form>
        </div>
    </div></div>
@endsection


