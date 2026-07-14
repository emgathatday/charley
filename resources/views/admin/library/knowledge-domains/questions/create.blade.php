@extends('layouts.master')

@section('title', 'Create Quiz Question')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Create Quiz Question</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}">Knowledge Domains</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.knowledge-domains.edit', $domain) }}">{{ $domain->name }}</a></li><li class="breadcrumb-item active" aria-current="page">Create Question</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        <div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h4 mb-1">{{ $domain->name }}</h2><div class="text-body-secondary">Create a nested question inside this Knowledge Domain.</div></div><a href="{{ route('admin.dashboard.library.knowledge-domains.edit', $domain) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Domain</a></div>
        @include('admin.library.knowledge-domains.questions._form')
    </div></div>
@endsection
