@extends('layouts.master')

@section('title', 'Taxonomy')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">Taxonomy</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Taxonomy</li>
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

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-primary"><i class="bi bi-tags"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Tags</span>
                            <span class="info-box-number">{{ $stats['total'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-info"><i class="bi bi-tools"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Technical</span>
                            <span class="info-box-number">{{ $stats['technical'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-warning"><i class="bi bi-cpu"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Equipment</span>
                            <span class="info-box-number">{{ $stats['equipment'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon text-bg-success"><i class="bi bi-diagram-3"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Process</span>
                            <span class="info-box-number">{{ $stats['process'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <p class="text-body-secondary mb-0">Review shared tags, category filters, and usage counts.</p>
                <a href="{{ route('admin.dashboard.taxonomy.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>
                    Create Tag
                </a>
            </div>

            <div class="card card-outline card-primary mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Filters</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.dashboard.taxonomy.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="search" class="form-label">Search</label>
                            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Name or slug">
                        </div>
                        <div class="col-md-4">
                            <x-taxonomy.category-filter :categories="$categories" :selected="$filters['category'] ?? null" />
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i>
                                Filter
                            </button>
                            <a href="{{ route('admin.dashboard.taxonomy.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            @include('admin.taxonomy._selector-preview')

            <div class="card card-outline card-primary">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Tags</h3>
                    <span class="badge text-bg-light">{{ $tags->total() }} total</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Category</th>
                                    <th class="text-center">Usage</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tags as $tag)
                                    <tr>
                                        <td class="fw-semibold">{{ $tag->name }}</td>
                                        <td><code>{{ $tag->slug }}</code></td>
                                        <td>
                                            <span class="badge text-bg-secondary">
                                                {{ $tag->category ? Str::headline(str_replace('_', ' ', $tag->category)) : 'Uncategorized' }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $tag->usage_count }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.dashboard.taxonomy.edit', $tag) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil-square me-1"></i>
                                                Edit
                                            </a>
                                            <button type="submit" form="delete-tag-{{ $tag->id }}" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash me-1"></i>
                                                Delete
                                            </button>
                                            <form id="delete-tag-{{ $tag->id }}" method="POST" action="{{ route('admin.dashboard.taxonomy.destroy', $tag) }}" class="d-none" onsubmit="return confirm('Delete this tag?');">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="text-center py-5">
                                                <i class="bi bi-tags display-6 text-body-secondary"></i>
                                                <h2 class="h5 mt-3 mb-1">No tags found</h2>
                                                <p class="text-body-secondary mb-3">Create the first taxonomy tag for reusable classification.</p>
                                                <a href="{{ route('admin.dashboard.taxonomy.create') }}" class="btn btn-primary">
                                                    <i class="bi bi-plus-circle me-1"></i>
                                                    Create Tag
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($tags->hasPages())
                    <div class="card-footer">
                        {{ $tags->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
