@extends('layouts.app')

@section('title', 'Edit Platform Setting')

@section('content_header')
    <div class="page-header"><div><div class="page-title">Platform Setting</div><div class="page-subtitle">Configure a global key, value, group, and governance note.</div></div><div class="page-actions"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back to Operations</a></div></div>
@endsection

@section('content')
    @include('templates.components.alert-session')
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('admin.dashboard.admin-operations.platform-settings.store') }}" class="form-card">
        @csrf
        <div class="form-card-header"><div class="form-card-icon blue"><i class="bi bi-gear"></i></div><div><div class="form-card-title">Setting Value</div><div class="form-card-sub">Changes apply platform-wide after saving.</div></div></div>
        <div class="form-card-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="key">Key</label><input id="key" type="text" class="form-control" name="key" value="{{ old('key', $platformSetting?->key) }}" placeholder="support.auto_assign_enabled" required></div>
            <div class="col-md-6"><label class="form-label" for="group">Group</label><input id="group" type="text" class="form-control" name="group" value="{{ old('group', $platformSetting?->group) }}" placeholder="support" required></div>
            <div class="col-12"><label class="form-label" for="value">Value</label><input id="value" type="text" class="form-control" name="value" value="{{ old('value', $platformSetting?->value) }}" required></div>
            <div class="col-12"><label class="form-label" for="description">Description</label><input id="description" type="text" class="form-control" name="description" value="{{ old('description', $platformSetting?->description) }}"></div>
        </div></div>
        <div class="page-actions" style="justify-content:flex-end;"><a href="{{ route('admin.dashboard.admin-operations.index') }}" class="btn">Cancel</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Save Setting</button></div>
    </form>

    <div class="detail-grid" style="margin-top:20px;">
        <section class="form-card"><div class="form-card-header"><div class="form-card-icon amber"><i class="bi bi-exclamation-triangle"></i></div><div><div class="form-card-title">Static Placeholder</div><div class="form-card-sub">The full platform-settings HTML has broader toggles and danger actions that are not route-backed yet.</div></div></div><div class="form-card-body"><div class="info-grid"><div class="info-item"><div class="info-label">Current Contract</div><div class="info-value">key, value, group, description</div></div><div class="info-item"><div class="info-label">Route</div><div class="info-value">platform-settings.store</div></div></div></div></section>
    </div>
@endsection
