@extends('layouts.master')

@section('title', 'Plant Types')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">Plant Types</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Plant Types</li>
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

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <div>
                    <p class="text-body-secondary mb-0">Manage shared plant taxonomy for partner profiles and platform content.</p>
                </div>
                <a href="{{ route('admin.dashboard.plant-types.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>
                    Create Plant Type
                </a>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Plant Type List</h3>
                    <span class="badge text-bg-light">{{ $plantTypes->total() }} total</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th class="text-center">Sort</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($plantTypes as $plantType)
                                    <tr>
                                        <td class="fw-semibold">{{ $plantType->name }}</td>
                                        <td><code>{{ $plantType->slug }}</code></td>
                                        <td class="text-body-secondary">
                                            {{ $plantType->description ? Str::limit($plantType->description, 90) : 'No description' }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $plantType->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                {{ $plantType->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $plantType->sort_order }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.dashboard.plant-types.edit', $plantType) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil-square me-1"></i>
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="text-center py-5">
                                                <i class="bi bi-diagram-3 display-6 text-body-secondary"></i>
                                                <h2 class="h5 mt-3 mb-1">No plant types yet</h2>
                                                <p class="text-body-secondary mb-3">Create the first plant category to enable shared taxonomy.</p>
                                                <a href="{{ route('admin.dashboard.plant-types.create') }}" class="btn btn-primary">
                                                    <i class="bi bi-plus-circle me-1"></i>
                                                    Create Plant Type
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($plantTypes->hasPages())
                    <div class="card-footer">
                        {{ $plantTypes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
