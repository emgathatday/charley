@extends('layouts.master')

@section('title', 'Feed CMS')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Feed CMS</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item active" aria-current="page">Feed CMS</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-warning"><i class="bi bi-pencil-square"></i></span><div class="info-box-content"><span class="info-box-text">Draft</span><span class="info-box-number">{{ $stats['draft'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-broadcast"></i></span><div class="info-box-content"><span class="info-box-text">Published</span><span class="info-box-number">{{ $stats['published'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-secondary"><i class="bi bi-archive"></i></span><div class="info-box-content"><span class="info-box-text">Archived</span><span class="info-box-number">{{ $stats['archived'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-info"><i class="bi bi-file-earmark-lock"></i></span><div class="info-box-content"><span class="info-box-text">System Pages</span><span class="info-box-number">{{ $stats['system'] }}</span></div></div></div>
        </div>

        <div class="card card-outline card-primary mb-3">
            <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">CMS Pages</h3><a href="{{ route('admin.dashboard.feed-cms.pages.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Create Page</a></div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.dashboard.feed-cms.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-5"><label for="search" class="form-label">Search</label><input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Title"></div>
                    <div class="col-md-4"><label for="status" class="form-label">Status</label><select id="status" name="status" class="form-select"><option value="">All statuses</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ Str::headline($status) }}</option>@endforeach</select></div>
                    <div class="col-md-3 d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button><a href="{{ route('admin.dashboard.feed-cms.index') }}" class="btn btn-outline-secondary">Reset</a></div>
                </form>
            </div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Page</th><th>Status</th><th>Views</th><th>Revisions</th><th>Updated</th><th class="text-end">Actions</th></tr></thead><tbody>
                @forelse ($pages as $page)
                    <tr><td><div class="fw-semibold">{{ $page->title }}</div><code>{{ $page->slug }}</code>@if ($page->is_system_page)<span class="badge text-bg-light ms-1">System</span>@endif</td><td><span class="badge text-bg-{{ $page->status === 'published' ? 'success' : ($page->status === 'archived' ? 'secondary' : 'warning') }}">{{ Str::headline($page->status) }}</span></td><td>{{ $page->view_count }}</td><td>{{ $page->revisions_count }}</td><td>{{ optional($page->updated_at)->format('Y-m-d H:i') }}</td><td class="text-end"><a href="{{ route('admin.dashboard.feed-cms.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a></td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-5">No CMS pages found.</td></tr>
                @endforelse
            </tbody></table></div></div>
            @if ($pages->hasPages())<div class="card-footer">{{ $pages->withQueryString()->links() }}</div>@endif
        </div>

        <div class="card card-outline card-info"><div class="card-header"><h3 class="card-title mb-0">Homepage Feed Priorities</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Content Type</th><th>Weight</th><th>Highlight</th><th>Color</th><th>Active</th><th class="text-end">Save</th></tr></thead><tbody>
            @foreach ($contentTypes as $contentType)
                @php($priority = $priorities->get($contentType))
                <tr><form method="POST" action="{{ route('admin.dashboard.feed-cms.priorities.update', $contentType) }}">@csrf @method('PUT')<td class="fw-semibold">{{ Str::headline(str_replace('_', ' ', $contentType)) }}</td><td><input type="number" name="priority_weight" value="{{ $priority?->priority_weight ?? 0 }}" class="form-control form-control-sm" min="-100" max="1000"></td><td class="text-center"><input type="checkbox" name="is_highlighted" value="1" class="form-check-input" @checked($priority?->is_highlighted)></td><td><input type="text" name="highlight_color" value="{{ $priority?->highlight_color }}" class="form-control form-control-sm" placeholder="#0d6efd"></td><td class="text-center"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($priority?->is_active ?? true)></td><td class="text-end"><button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-save me-1"></i>Save</button></td></form></tr>
            @endforeach
        </tbody></table></div></div></div>
    </div></div>
@endsection
