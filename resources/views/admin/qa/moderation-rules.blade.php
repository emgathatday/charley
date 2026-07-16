@extends('layouts.master')

@section('title', 'QA Moderation Rules')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">QA Moderation Rules</h1></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.qa.index') }}">QA</a></li><li class="breadcrumb-item active" aria-current="page">Moderation Rules</li></ol></div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @include('templates.components.alert-session')
            @include('admin.qa.components.action-tabs')

            <div class="row g-3">
                <div class="col-lg-5">
                    @include('admin.qa.components.moderation-rule-form')
                </div>
                <div class="col-lg-7">
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Active Rule Inventory</h3></div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Rule</th><th>Config</th><th>State</th><th class="text-end">Controls</th></tr></thead>
                                <tbody>
                                @forelse ($rules as $rule)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $rule->name }}</div>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                <span class="badge text-bg-info">{{ Str::headline($rule->rule_type) }}</span>
                                                <span class="badge text-bg-light border">{{ Str::headline($rule->target_type) }}</span>
                                                <span class="badge text-bg-{{ $rule->severity === 'high' ? 'danger' : ($rule->severity === 'medium' ? 'warning' : 'secondary') }}">{{ Str::headline($rule->severity) }}</span>
                                            </div>
                                            <div class="small text-body-secondary mt-1">Created by {{ $rule->creator_name }}</div>
                                        </td>
                                        <td><pre class="small mb-0 text-wrap">{{ $rule->config_text }}</pre></td>
                                        <td><span class="badge text-bg-{{ $rule->is_active ? 'success' : 'secondary' }}">{{ $rule->is_active ? 'Runs before AI' : 'Inactive' }}</span></td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.dashboard.qa.moderation-rules.toggle', $rule->id) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $rule->is_active ? 'Disable' : 'Enable' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.dashboard.qa.moderation-rules.update', $rule->id) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="name" value="{{ $rule->name }}">
                                                <input type="hidden" name="rule_type" value="{{ $rule->rule_type }}">
                                                <input type="hidden" name="target_type" value="{{ $rule->target_type }}">
                                                <input type="hidden" name="severity" value="{{ $rule->severity }}">
                                                <input type="hidden" name="is_active" value="{{ $rule->is_active ? 1 : 0 }}">
                                                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-pencil-square me-1"></i>Demo Edit</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-muted text-center py-3" colspan="4">No moderation rules configured.</td></tr>
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