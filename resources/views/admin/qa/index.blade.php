@extends('layouts.master')

@section('title', 'QA Dashboard & Questions')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">QA Dashboard & Questions</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">QA</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @include('templates.components.alert-session')
            @include('admin.qa.components.action-tabs')

            <div class="row g-3 mb-3">
                @foreach ($stats as $stat)
                    @include('admin.qa.components.stats-card', $stat)
                @endforeach
            </div>

            <div class="card card-outline card-primary mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Question Review Filters</h3>
                    <a href="{{ route('admin.dashboard.qa.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
                <div class="card-body pb-0">
                    <form method="GET" action="{{ route('admin.dashboard.qa.index') }}" class="row g-3 align-items-end mb-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="keyword">Keyword</label>
                            <input id="keyword" name="keyword" class="form-control" value="{{ $filters['keyword'] ?? '' }}" placeholder="Title, body, author">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">All statuses</option>
                                @foreach (['pending', 'published', 'hidden', 'flagged'] as $status)
                                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ Str::headline($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label class="form-label" for="plant_type_id">Plant type</label>
                            <select id="plant_type_id" name="plant_type_id" class="form-select">
                                <option value="">All plant types</option>
                                @foreach ($plantTypes as $plantType)
                                    <option value="{{ $plantType->id }}" @selected(($filters['plant_type_id'] ?? '') == $plantType->id)>{{ $plantType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <label class="form-label" for="weekly_theme_id">Weekly theme</label>
                            <select id="weekly_theme_id" name="weekly_theme_id" class="form-select">
                                <option value="">All themes</option>
                                @foreach ($weeklyThemes as $weeklyTheme)
                                    <option value="{{ $weeklyTheme->id }}" @selected(($filters['weekly_theme_id'] ?? '') == $weeklyTheme->id)>{{ $weeklyTheme->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-1">
                            <label class="form-label" for="date_from">From</label>
                            <input id="date_from" name="date_from" type="date" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-md-4 col-lg-1">
                            <label class="form-label" for="date_to">To</label>
                            <input id="date_to" name="date_to" type="date" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-lg-1 d-flex gap-2">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
                        </div>
                    </form>
                    @include('admin.qa.components.question-review-table', ['questions' => $questions])
                </div>
            </div>
        </div>
    </div>
@endsection