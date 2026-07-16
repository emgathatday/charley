@extends('layouts.master')

@section('title', 'QA Flagged Content')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">QA Flagged Content</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.qa.index') }}">QA</a></li><li class="breadcrumb-item active" aria-current="page">Flagged Content</li></ol></div></div></div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @include('templates.components.alert-session')
            @include('admin.qa.components.action-tabs')

            <div class="card card-outline card-primary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Flagged Filters</h3></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.dashboard.qa.flagged') }}" class="row g-3 align-items-end">
                        <div class="col-md-3"><label class="form-label" for="flag_status">Status</label><select id="flag_status" name="flag_status" class="form-select"><option value="">Flagged only</option><option value="flagged" @selected(($filters['flag_status'] ?? '') === 'flagged')>Flagged</option><option value="hidden" @selected(($filters['flag_status'] ?? '') === 'hidden')>Hidden</option></select></div>
                        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button><a href="{{ route('admin.dashboard.qa.flagged') }}" class="btn btn-outline-secondary">Reset</a></div>
                    </form>
                </div>
            </div>

            <div class="row">
                @forelse ($questions as $item)
                    <div class="col-lg-6">
                        @include('admin.qa.components.flagged-card', ['item' => $item])
                    </div>
                @empty
                    <div class="col-12"><div class="card"><div class="card-body text-center text-muted">No flagged QA content.</div></div></div>
                @endforelse
            </div>
        </div>
    </div>
@endsection