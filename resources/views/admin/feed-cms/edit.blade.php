@extends('layouts.master')

@section('title', 'Edit CMS Page')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Edit CMS Page</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.feed-cms.index') }}">Feed CMS</a></li><li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')

        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
            <div><span class="badge text-bg-{{ $page->status === 'published' ? 'success' : ($page->status === 'archived' ? 'secondary' : 'warning') }}">{{ Str::headline($page->status) }}</span><span class="text-body-secondary ms-2">{{ $page->slug }}</span></div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.dashboard.feed-cms.pages.publish', $page) }}">@csrf<button type="submit" class="btn btn-success"><i class="bi bi-broadcast me-1"></i>Publish</button></form>
                <form method="POST" action="{{ route('admin.dashboard.feed-cms.pages.archive', $page) }}">@csrf<button type="submit" class="btn btn-outline-secondary"><i class="bi bi-archive me-1"></i>Archive</button></form>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.dashboard.feed-cms.pages.update', $page) }}" class="mb-3">
            @include('admin.feed-cms._form', ['method' => 'PUT'])
        </form>

        <div class="card card-outline card-info"><div class="card-header"><h3 class="card-title mb-0">Revision History</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Revision</th><th>Changed By</th><th>Summary</th><th>Created</th><th class="text-end">Rollback</th></tr></thead><tbody>
            @forelse ($revisions as $revision)
                <tr><td>#{{ $revision->id }}</td><td>{{ $revision->changer?->name ?? 'System' }}</td><td>{{ $revision->change_summary ?? 'No summary' }}</td><td>{{ optional($revision->created_at)->format('Y-m-d H:i') }}</td><td class="text-end"><form method="POST" action="{{ route('admin.dashboard.feed-cms.pages.revisions.rollback', [$page, $revision]) }}" onsubmit="return confirm('Rollback this page revision?');">@csrf<button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-counterclockwise me-1"></i>Rollback</button></form></td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-body-secondary py-4">No revisions recorded.</td></tr>
            @endforelse
        </tbody></table></div></div></div>
    </div></div>
@endsection
