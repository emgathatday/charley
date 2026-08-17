@extends('layouts.master')

@section('title', 'QA Leaderboard Settings')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">QA Leaderboard Settings</h1></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.qa.index') }}">QA</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Leaderboard Settings</li>
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

            <div class="card card-outline card-primary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Leaderboard Controls</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.dashboard.qa.leaderboard.settings.store') }}" class="row g-3 align-items-end mb-3">
                        @csrf
                        <div class="col-md-3"><label class="form-label" for="min_points_threshold">Minimum points</label><input id="min_points_threshold" name="min_points_threshold" type="number" class="form-control" value="{{ $settings['min_points_threshold'] }}"></div>
                        <div class="col-md-3"><label class="form-label" for="top_n">Top N</label><input id="top_n" name="top_n" type="number" class="form-control" value="{{ $settings['top_n'] }}"></div>
                        <div class="col-md-3"><label class="form-label" for="effective_from">Effective from</label><input id="effective_from" name="effective_from" type="date" class="form-control" value="{{ $settings['effective_from'] }}"></div>
                        <div class="col-md-3 d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Settings</button><a href="{{ route('admin.dashboard.qa.leaderboard') }}" class="btn btn-outline-secondary">Reset</a></div>
                    </form>
                    <form method="POST" action="{{ route('admin.dashboard.qa.leaderboard.snapshot') }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-3"><label class="form-label" for="year_month">Snapshot month</label><input id="year_month" name="year_month" type="month" class="form-control" value="{{ $filters['year_month'] ?? now()->format('Y-m') }}"></div>
                        <div class="col-md-3 d-flex gap-2"><button type="submit" class="btn btn-outline-primary"><i class="bi bi-camera me-1"></i>Refresh Snapshot</button><a href="{{ route('admin.dashboard.qa.leaderboard-report') }}" class="btn btn-outline-secondary">View Report</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection