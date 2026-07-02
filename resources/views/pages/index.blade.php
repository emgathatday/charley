@extends('layouts.master')

@section('title', 'Pages')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">Pages</h1></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('feed.index') }}">Feed</a></li><li class="breadcrumb-item active" aria-current="page">Pages</li></ol></div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Published CMS Pages</h3></div>
            <div class="card-body">
                <form method="GET" action="{{ route('pages.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-9"><label for="search" class="form-label">Search</label><input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search published pages"></div>
                    <div class="col-md-3 d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button><a href="{{ route('pages.index') }}" class="btn btn-outline-secondary">Reset</a></div>
                </form>
            </div>
        </div>

        <div class="row g-3">
            @forelse ($pages as $page)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 card-outline card-light">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between gap-2 mb-2"><span class="badge text-bg-success">Published</span><span class="text-body-secondary small">{{ optional($page->published_at)->format('Y-m-d') }}</span></div>
                            <h2 class="h5 mb-2"><a href="{{ route('pages.show', $page->slug) }}" class="link-dark text-decoration-none">{{ $page->title }}</a></h2>
                            <p class="text-body-secondary small mb-3">{{ $page->seo_meta['description'] ?? 'API-ready CMS page preview.' }}</p>
                            <div class="mt-auto d-flex justify-content-between align-items-center"><code>{{ $page->slug }}</code><a href="{{ route('pages.show', $page->slug) }}" class="btn btn-sm btn-outline-primary">Open</a></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="card"><div class="card-body text-center text-body-secondary py-5">No published pages found.</div></div></div>
            @endforelse
        </div>

        @if ($pages->hasPages())<div class="mt-3">{{ $pages->withQueryString()->links() }}</div>@endif
    </div></div>
@endsection
