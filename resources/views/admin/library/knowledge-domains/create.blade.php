@extends('layouts.master')

@section('title', 'Create Knowledge Domain')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Create Knowledge Domain</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}">Knowledge Domains</a></li><li class="breadcrumb-item active" aria-current="page">Create</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')

        <form method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.store') }}">
            @csrf
            @include('admin.library.knowledge-domains._form')
            <div class="card card-outline card-info mb-3"><div class="card-header"><h3 class="card-title mb-0">Embedded Quiz Setup</h3></div><div class="card-body"><p class="text-body-secondary mb-0">Create the domain first, then add and edit quiz questions inside this domain edit page. No separate Quiz Questions route is exposed.</p></div></div>
            <div class="d-flex justify-content-end gap-2 mb-3"><a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Domain</button></div>
        </form>
    </div></div>
@endsection
