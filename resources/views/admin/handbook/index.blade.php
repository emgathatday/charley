@extends('layouts.master')

@section('title', 'Handbook')

@section('content')
@php
    $stats = $stats ?? ['categories' => 0, 'articles' => 0, 'published' => 0, 'drafts' => 0, 'hotspots' => 0];
    $categories = collect($categories ?? []);
    $hotspots = collect($hotspots ?? []);
@endphp

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1>Handbook</h1></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="#">Admin</a></li><li class="breadcrumb-item active">Handbook</li></ol></div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ $stats['categories'] }}</h3><p>Categories</p></div><div class="icon"><i class="fas fa-sitemap"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ $stats['published'] }}</h3><p>Published Articles</p></div><div class="icon"><i class="fas fa-book-open"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ $stats['drafts'] }}</h3><p>Draft Articles</p></div><div class="icon"><i class="fas fa-edit"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3>{{ $stats['hotspots'] }}</h3><p>Layout Hotspots</p></div><div class="icon"><i class="fas fa-map-marker-alt"></i></div></div></div>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Category Tree</h3></div>
                        <div class="card-body p-0">
                            @forelse($categories->whereNull('parent_id') as $category)
                                <div class="p-3 border-bottom">
                                    <div class="d-flex justify-content-between"><strong>{{ $category->title }}</strong><span class="badge badge-{{ $category->status === 'published' ? 'success' : 'secondary' }}">{{ $category->status }}</span></div>
                                    <div class="text-muted small">{{ $category->articles_count ?? $category->articles()->count() }} articles</div>
                                    @if($category->children->isNotEmpty())
                                        <ul class="mb-0 mt-2 pl-3">
                                            @foreach($category->children as $child)<li>{{ $child->title }}</li>@endforeach
                                        </ul>
                                    @endif
                                </div>
                            @empty
                                <div class="p-3 text-muted">No handbook categories yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="card card-info">
                        <div class="card-header"><h3 class="card-title">Hotspot Preview</h3></div>
                        <div class="card-body">
                            <div class="position-relative border rounded bg-light" style="height: 220px;">
                                @forelse($hotspots as $hotspot)
                                    @php $coords = $hotspot['map_coordinates'] ?? []; @endphp
                                    <span class="badge badge-danger position-absolute" style="left: {{ $coords['x'] ?? 5 }}%; top: {{ $coords['y'] ?? 5 }}%;">{{ $hotspot['title'] ?? 'Hotspot' }}</span>
                                @empty
                                    <span class="text-muted position-absolute" style="left: 1rem; top: 1rem;">No layout hotspots yet.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Articles</h3><div class="card-tools"><a href="{{ route('admin.dashboard.handbook.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i>New Article</a></div></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead><tr><th>Title</th><th>Category</th><th>Metadata</th><th>Status</th><th>Views</th><th></th></tr></thead>
                                <tbody>
                                    @forelse($articles as $article)
                                        <tr>
                                            <td>{{ $article->title }}</td>
                                            <td>{{ $article->category?->title ?? 'Uncategorized' }}</td>
                                            <td>{{ $article->metadata->pluck('meta_type')->unique()->implode(', ') ?: 'None' }}</td>
                                            <td><span class="badge badge-{{ $article->status === 'published' ? 'success' : 'secondary' }}">{{ $article->status }}</span></td>
                                            <td>{{ $article->view_count }}</td>
                                            <td class="text-right"><a href="{{ route('admin.dashboard.handbook.show', $article) }}" class="btn btn-xs btn-outline-primary">View</a><a href="{{ route('admin.dashboard.handbook.edit', $article) }}" class="btn btn-xs btn-outline-secondary">Edit</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted">No handbook articles yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($articles, 'links'))<div class="card-footer">{{ $articles->links() }}</div>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection