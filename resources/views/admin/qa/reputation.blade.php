@extends('layouts.master')

@section('title', 'QA Reputation')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">QA Reputation</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.qa.index') }}">QA</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reputation</li>
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
                <div class="col-md-4">
                    <div class="info-box mb-0">
                        <span class="info-box-icon text-bg-primary"><i class="bi bi-stars"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Tracked Reputation Users</span>
                            <span class="info-box-number">{{ $reputationUsers->count() }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box mb-0">
                        <span class="info-box-icon text-bg-success"><i class="bi bi-plus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Positive Ledger Points</span>
                            <span class="info-box-number">+{{ $ledger->where('points', '>', 0)->sum('points') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box mb-0">
                        <span class="info-box-icon text-bg-danger"><i class="bi bi-dash-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Negative Ledger Points</span>
                            <span class="info-box-number">{{ $ledger->where('points', '<', 0)->sum('points') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Reputation Filters</h3></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.dashboard.qa.reputation') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label" for="keyword">Search user or reason</label>
                            <input id="keyword" name="keyword" type="search" class="form-control" value="{{ $filters['keyword'] ?? '' }}" placeholder="Name, email, reason">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="source_type">Source</label>
                            <select id="source_type" name="source_type" class="form-select">
                                <option value="">All sources</option>
                                <option value="question" @selected(($filters['source_type'] ?? '') === 'question')>Question</option>
                                <option value="answer" @selected(($filters['source_type'] ?? '') === 'answer')>Answer</option>
                                <option value="manual_adjustment" @selected(($filters['source_type'] ?? '') === 'manual_adjustment')>Manual Adjustment</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
                            <a href="{{ route('admin.dashboard.qa.reputation') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    @include('admin.qa.components.reputation-form', ['users' => $users])
                </div>
                <div class="col-lg-7">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">User Reputation Rows</h3></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>User</th><th>Email</th><th>Total Points</th><th>Current Star Rank</th></tr></thead>
                                <tbody>
                                @forelse ($reputationUsers as $user)
                                    <tr>
                                        <td class="fw-semibold">{{ $user->display_name }}</td>
                                        <td class="text-body-secondary">{{ $user->email }}</td>
                                        <td><span class="badge text-bg-{{ $user->total_points >= 0 ? 'success' : 'danger' }}">{{ $user->total_points }}</span></td>
                                        <td><span class="text-warning">{{ str_repeat('★', max(1, (int) $user->current_star_rank)) }}</span> <span class="small text-body-secondary">Rank {{ $user->current_star_rank }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td class="text-muted text-center py-3" colspan="4">No reputation users available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h3 class="card-title mb-0">Recent Point Transactions</h3></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-striped align-middle mb-0">
                                <thead><tr><th>User</th><th>Points</th><th>Source</th><th>Reason</th><th>Performed By</th></tr></thead>
                                <tbody>
                                @forelse ($ledger as $entry)
                                    <tr>
                                        <td>{{ $entry->display_name }}</td>
                                        <td class="fw-semibold {{ $entry->points >= 0 ? 'text-success' : 'text-danger' }}">{{ $entry->points > 0 ? '+'.$entry->points : $entry->points }}</td>
                                        <td><span class="badge text-bg-light border">{{ Str::headline($entry->source_type) }}</span></td>
                                        <td>{{ $entry->reason }}</td>
                                        <td class="text-body-secondary">{{ $entry->performed_by_name }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="text-muted text-center py-3" colspan="5">No reputation transactions available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection