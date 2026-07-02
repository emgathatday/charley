@extends('layouts.master')

@section('title', 'Create CMS Page')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Create CMS Page</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.feed-cms.index') }}">Feed CMS</a></li><li class="breadcrumb-item active" aria-current="page">Create</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        <form method="POST" action="{{ route('admin.dashboard.feed-cms.pages.store') }}">
            @include('admin.feed-cms._form')
        </form>
    </div></div>
@endsection
