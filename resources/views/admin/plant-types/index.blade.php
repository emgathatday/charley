@extends('templates.layouts.admin')

@section('content')
    <div class="container-fluid">
        @include('templates.components.alert-session')

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-0">Plant Types</h1>
                <p class="text-muted mb-0">Manage shared plant type taxonomy.</p>
            </div>
            <a href="{{ route('admin.dashboard.plant-types.create') }}" class="btn btn-primary">
                Create Plant Type
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Plant type list</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Sort Order</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plantTypes as $plantType)
                            <tr>
                                <td>{{ $plantType->name }}</td>
                                <td>{{ $plantType->slug }}</td>
                                <td>
                                    <span class="badge {{ $plantType->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $plantType->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $plantType->sort_order }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.dashboard.plant-types.edit', $plantType) }}" class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No plant types found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($plantTypes->hasPages())
                <div class="card-footer">
                    {{ $plantTypes->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
