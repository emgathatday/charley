@extends('layouts.master')

@section('title', 'QA Warning Review')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">QA Warning Review</h1></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.qa.index') }}">QA</a></li><li class="breadcrumb-item active" aria-current="page">Warnings</li></ol></div>
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
                <div class="col-md-4">
                    <div class="info-box mb-0"><span class="info-box-icon text-bg-primary"><i class="bi bi-shield-exclamation"></i></span><div class="info-box-content"><span class="info-box-text">Pending Review</span><span class="info-box-number">{{ $warnings->where('status', 'pending_review')->count() }}</span></div></div>
                </div>
                <div class="col-md-4">
                    <div class="info-box mb-0"><span class="info-box-icon text-bg-warning"><i class="bi bi-person-exclamation"></i></span><div class="info-box-content"><span class="info-box-text">Near Freeze</span><span class="info-box-number">{{ $warningSummaries->where('confirmed_warning_count', '>=', 2)->where('is_frozen', false)->count() }}</span></div></div>
                </div>
                <div class="col-md-4">
                    <div class="info-box mb-0"><span class="info-box-icon text-bg-danger"><i class="bi bi-snow"></i></span><div class="info-box-content"><span class="info-box-text">Frozen Users</span><span class="info-box-number">{{ $warningSummaries->where('is_frozen', true)->count() }}</span></div></div>
                </div>
            </div>

            <div class="card card-outline card-primary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Warning Filters</h3></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.dashboard.qa.warnings') }}" class="row g-3 align-items-end">
                        <div class="col-md-3"><label class="form-label" for="status">Status</label><select id="status" name="status" class="form-select"><option value="">Pending review</option>@foreach (['pending_review', 'safe', 'confirmed', 'dismissed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ Str::headline($status) }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label" for="source">Source</label><select id="source" name="source" class="form-select"><option value="">All sources</option>@foreach (['system_rule', 'ai', 'admin'] as $source)<option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ Str::headline($source) }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label" for="severity">Severity</label><select id="severity" name="severity" class="form-select"><option value="">All severities</option>@foreach (['low', 'medium', 'high'] as $severity)<option value="{{ $severity }}" @selected(($filters['severity'] ?? '') === $severity)>{{ Str::headline($severity) }}</option>@endforeach</select></div>
                        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button><a href="{{ route('admin.dashboard.qa.warnings') }}" class="btn btn-outline-secondary">Reset</a></div>
                    </form>
                </div>
            </div>

            <div class="alert alert-info">
                <div class="fw-semibold"><i class="bi bi-info-circle me-1"></i>Confirmed warnings drive freeze</div>
                <div class="small">system_rule, ai, and admin warnings start in pending review. Only confirmed warnings count toward the 3-warning freeze threshold.</div>
            </div>

            @include('admin.qa.components.warning-review-table', ['warnings' => $warnings])
        </div>
    </div>
@endsection