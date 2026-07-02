@extends('layouts.master')

@section('title', 'Feed')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">Feed</h1></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('pages.index') }}">Pages</a></li><li class="breadcrumb-item active" aria-current="page">Feed</li></ol></div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        <div class="card card-outline card-primary mb-3"><div class="card-body"><form method="GET" action="{{ route('feed.index') }}" class="row g-3 align-items-end"><div class="col-md-9"><label for="search" class="form-label">Search Feed</label><input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search feed and CMS pages"></div><div class="col-md-3 d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button><a href="{{ route('feed.index') }}" class="btn btn-outline-secondary">Reset</a></div></form></div></div>

        @auth
            <div class="card card-outline card-info mb-3"><div class="card-header"><h3 class="card-title mb-0">Personalized Feed Preview</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Item</th><th>Reason</th><th>Score</th><th>Seen</th></tr></thead><tbody>
                @forelse ($personalizedItems as $item)
                    <tr><td>{{ $item->feedable?->title ?? class_basename($item->feedable_type) . ' #' . $item->feedable_id }}</td><td>{{ Str::headline(str_replace('_', ' ', $item->source_reason)) }}</td><td>{{ $item->priority_score }}</td><td><span class="badge text-bg-{{ $item->is_seen ? 'secondary' : 'success' }}">{{ $item->is_seen ? 'Seen' : 'New' }}</span></td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-body-secondary py-4">No personalized feed cache found.</td></tr>
                @endforelse
            </tbody></table></div></div></div>
        @endauth

        <div class="card card-outline card-success"><div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Published CMS Feed</h3><a href="{{ route('pages.index') }}" class="btn btn-sm btn-outline-primary">All Pages</a></div><div class="card-body p-0"><div class="list-group list-group-flush">
            @forelse ($publishedPages as $page)
                <a href="{{ route('pages.show', $page->slug) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-3"><span><span class="fw-semibold d-block">{{ $page->title }}</span><span class="text-body-secondary small">{{ $page->seo_meta['description'] ?? $page->slug }}</span></span><span class="badge text-bg-light align-self-start">{{ optional($page->published_at)->format('Y-m-d') }}</span></a>
            @empty
                <div class="list-group-item text-center text-body-secondary py-4">No published feed items found.</div>
            @endforelse
        </div></div></div>
    </div></div>
@endsection
