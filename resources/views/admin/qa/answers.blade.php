@extends('layouts.master')

@section('title', 'QA Answer Moderation')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">QA Answer Moderation</h1></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.qa.index') }}">QA</a></li><li class="breadcrumb-item active" aria-current="page">Answers</li></ol></div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @include('templates.components.alert-session')
            @include('admin.qa.components.action-tabs')
            <div class="card card-outline card-primary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Answer Filters</h3></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.dashboard.qa.answers') }}" class="row g-3 align-items-end">
                        <div class="col-md-3"><label class="form-label" for="is_admin_featured">Featured state</label><select id="is_admin_featured" name="is_admin_featured" class="form-select"><option value="">All answers</option><option value="1" @selected(($filters['is_admin_featured'] ?? '') === '1')>Featured</option><option value="0" @selected(($filters['is_admin_featured'] ?? '') === '0')>Not featured</option></select></div>
                        <div class="col-md-3"><label class="form-label" for="question_id">Question ID</label><input id="question_id" name="question_id" type="number" class="form-control" value="{{ $filters['question_id'] ?? '' }}" placeholder="Optional"></div>
                        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button><a href="{{ route('admin.dashboard.qa.answers') }}" class="btn btn-outline-secondary">Reset</a></div>
                    </form>
                </div>
            </div>
            @include('admin.qa.components.answer-review-table', ['answers' => $answers])
        </div>
    </div>
@endsection