@extends('layouts.master')

@section('title', 'Edit Platform Setting')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Platform Setting</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.admin-operations.index') }}">Operations</a></li><li class="breadcrumb-item active">Setting</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title mb-0">Setting value</h3></div>
            <form method="POST" action="{{ route('admin.dashboard.admin-operations.platform-settings.store') }}">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Key</label><input type="text" class="form-control" name="key" placeholder="support.auto_assign_enabled"></div>
                        <div class="col-md-6"><label class="form-label">Group</label><input type="text" class="form-control" name="group" placeholder="support"></div>
                        <div class="col-12"><label class="form-label">Value</label><input type="text" class="form-control" name="value"></div>
                        <div class="col-12"><label class="form-label">Description</label><input type="text" class="form-control" name="description"></div>
                    </div>
                </div>
                <div class="card-footer text-end"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-secondary" type="submit">Save Draft</button></div>
            </form>
        </div>
    </div></div>
@endsection

