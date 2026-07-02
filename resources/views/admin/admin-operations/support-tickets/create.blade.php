@extends('layouts.master')

@section('title', 'Create Support Ticket')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Create Support Ticket</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.admin-operations.index') }}">Operations</a></li><li class="breadcrumb-item active">Ticket</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title mb-0">Ticket details</h3></div>
            <form method="POST" action="{{ route('admin.dashboard.admin-operations.support-tickets.store') }}">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">User</label><select class="form-select" name="user_id">@foreach ($users as $user)<option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->email }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Subject</label><input type="text" class="form-control" name="subject" placeholder="Support request subject"></div>
                        <div class="col-md-4"><label class="form-label">Category</label><select class="form-select" name="category"><option>subscription_support</option><option>technical_issue</option><option>content_approval</option><option>account_issue</option><option>other</option></select></div>
                        <div class="col-md-4"><label class="form-label">Priority</label><select class="form-select" name="priority"><option>normal</option><option>low</option><option>high</option><option>urgent</option></select></div>
                        <div class="col-md-4"><label class="form-label">Assigned admin</label><select class="form-select" name="assigned_to"><option value="">Unassigned</option>@foreach ($users->where('role', 'admin') as $user)<option value="{{ $user->id }}" @selected(old('assigned_to') == $user->id)>{{ $user->email }}</option>@endforeach</select></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="5"></textarea></div>
                    </div>
                </div>
                <div class="card-footer text-end"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit">Save Draft</button></div>
            </form>
        </div>
    </div></div>
@endsection


