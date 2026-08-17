@extends('layouts.master')

@section('title', 'Monthly Leaderboard Report')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">Monthly Leaderboard Report</h1></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.qa.index') }}">QA</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Monthly Leaderboard Report</li>
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
                <div class="card-header"><h3 class="card-title mb-0">Report Filters</h3></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.dashboard.qa.leaderboard-report') }}" class="row g-3 align-items-end">
                        <div class="col-md-3"><label class="form-label" for="year_month">Snapshot month</label><input id="year_month" name="year_month" type="month" class="form-control" value="{{ $filters['year_month'] ?? now()->format('Y-m') }}"></div>
                        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button><a href="{{ route('admin.dashboard.qa.leaderboard-report') }}" class="btn btn-outline-secondary">Reset</a></div>
                    </form>
                </div>
            </div>

            @include('admin.qa.components.leaderboard-table', ['leaders' => $leaders])
        </div>
    </div>
@endsection