@extends('layouts.master')

@section('title', 'Knowledge Domains')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Knowledge Domains</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item active" aria-current="page">Knowledge Domains</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-diagram-3"></i></span><div class="info-box-content"><span class="info-box-text">Domains</span><span class="info-box-number">{{ $stats['total'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-check2-circle"></i></span><div class="info-box-content"><span class="info-box-text">Active</span><span class="info-box-number">{{ $stats['active'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-info"><i class="bi bi-question-circle"></i></span><div class="info-box-content"><span class="info-box-text">Questions</span><span class="info-box-number">{{ $stats['questions'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-warning"><i class="bi bi-pencil-square"></i></span><div class="info-box-content"><span class="info-box-text">Draft Questions</span><span class="info-box-number">{{ $stats['draft_questions'] }}</span></div></div></div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Domain Library</h3>
                <a href="{{ route('admin.dashboard.library.knowledge-domains.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Create Domain</a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.dashboard.library.knowledge-domains.index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-4"><label class="form-label" for="search">Search</label><input id="search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Domain name, slug, or description"></div>
                    <div class="col-md-3"><label class="form-label" for="plant_type_id">Plant Type</label><select id="plant_type_id" name="plant_type_id" class="form-select"><option value="">All plant types</option>@foreach($plantTypes as $plantType)<option value="{{ $plantType->id }}" @selected((string) request('plant_type_id') === (string) $plantType->id)>{{ $plantType->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label" for="is_active">Status</label><select id="is_active" name="is_active" class="form-select"><option value="">All statuses</option><option value="1" @selected(request('is_active') === '1')>Active</option><option value="0" @selected(request('is_active') === '0')>Inactive</option></select></div>
                    <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button><a class="btn btn-outline-secondary" href="{{ route('admin.dashboard.library.knowledge-domains.index') }}">Reset</a></div>
                </form>
            </div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Domain</th><th>Plant Type</th><th>Questions</th><th>Quiz Health</th><th>Status</th><th>Sort</th><th class="text-end">Actions</th></tr></thead><tbody>
                @forelse ($domains as $domain)
                    <tr>
                        <td><div class="d-flex align-items-center gap-2"><i class="{{ $domain->icon ?: 'bi bi-diagram-3' }} text-primary"></i><div><div class="fw-semibold">{{ $domain->name }}</div><code>{{ $domain->slug }}</code><div class="small text-body-secondary">{{ Str::limit($domain->description, 92) }}</div></div></div></td>
                        <td>{{ $domain->plantType?->name ?? 'General' }}</td>
                        <td><span class="badge text-bg-info">{{ $domain->quiz_questions_count }} total</span><div class="small text-body-secondary">{{ $domain->active_questions_count }} active, {{ $domain->draft_questions_count }} draft</div></td>
                        <td><div class="progress" style="height: .5rem;"><div class="progress-bar bg-success" style="width: {{ min(100, max(12, $domain->active_questions_count * 18)) }}%"></div></div><div class="small text-body-secondary mt-1">Embedded manager only</div></td>
                        <td><span class="badge text-bg-{{ $domain->is_active ? 'success' : 'secondary' }}">{{ $domain->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $domain->sort_order }}</td>
                        <td class="text-end"><a href="{{ route('admin.dashboard.library.knowledge-domains.edit', $domain) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit Domain</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-body-secondary py-4">No knowledge domains match the current filters.</td></tr>
                @endforelse
            </tbody></table></div></div>
            @if($domains->hasPages())<div class="card-footer">{{ $domains->links() }}</div>@endif
        </div>
    </div></div>
@endsection
