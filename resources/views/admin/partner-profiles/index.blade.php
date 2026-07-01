@extends('layouts.master')

@section('title', 'Partner Profiles')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">Partner Profiles</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Partner Profiles</li>
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
                <p class="text-body-secondary mb-0">Review partner companies, approval state, products, presentations, and members.</p>
                <a href="{{ route('admin.dashboard.partner-profiles.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>
                    Create Partner
                </a>
            </div>

            <div class="card card-outline card-primary mb-3">
                <form method="GET" action="{{ route('admin.dashboard.partner-profiles.index') }}">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4">
                                <label for="search" class="form-label">Search</label>
                                <input id="search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Company name">
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label for="approval_status" class="form-label">Status</label>
                                <select id="approval_status" class="form-select" name="approval_status">
                                    <option value="">All statuses</option>
                                    @foreach (['pending','approved','rejected','suspended'] as $status)
                                        <option value="{{ $status }}" @selected(request('approval_status') === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label for="plant_type_id" class="form-label">Plant Type</label>
                                <select id="plant_type_id" class="form-select" name="plant_type_id">
                                    <option value="">All plant types</option>
                                    @foreach ($plantTypes as $plantType)
                                        <option value="{{ $plantType->id }}" @selected((string) request('plant_type_id') === (string) $plantType->id)>{{ $plantType->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-2 d-flex gap-2">
                                <button class="btn btn-primary flex-fill" type="submit">
                                    <i class="bi bi-funnel me-1"></i>
                                    Filter
                                </button>
                                <a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="btn btn-outline-secondary" aria-label="Reset filters">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Partner Queue</h3>
                    <span class="badge text-bg-light">{{ $partnerProfiles->total() }} total</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Tier</th>
                                    <th>Plant Type</th>
                                    <th>Status</th>
                                    <th class="text-center">Products</th>
                                    <th class="text-center">Presentations</th>
                                    <th class="text-center">Members</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($partnerProfiles as $partnerProfile)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $partnerProfile->company_name }}</div>
                                            <div class="small text-body-secondary">{{ $partnerProfile->contact_email ?? 'No contact email' }}</div>
                                        </td>
                                        <td>{{ $partnerProfile->partner_tier ? ucfirst($partnerProfile->partner_tier) : '-' }}</td>
                                        <td>{{ $partnerProfile->plantType?->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge @class([
                                                'text-bg-warning' => $partnerProfile->approval_status === 'pending',
                                                'text-bg-success' => $partnerProfile->approval_status === 'approved',
                                                'text-bg-danger' => $partnerProfile->approval_status === 'rejected',
                                                'text-bg-secondary' => $partnerProfile->approval_status === 'suspended',
                                            ])">
                                                {{ ucfirst($partnerProfile->approval_status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $partnerProfile->products_count }}</td>
                                        <td class="text-center">{{ $partnerProfile->presentations_count }}</td>
                                        <td class="text-center">{{ $partnerProfile->members_count }}</td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.dashboard.partner-profiles.show', $partnerProfile) }}" class="btn btn-outline-primary">
                                                    <i class="bi bi-eye me-1"></i>
                                                    View
                                                </a>
                                                <a href="{{ route('admin.dashboard.partner-profiles.edit', $partnerProfile) }}" class="btn btn-outline-secondary">
                                                    <i class="bi bi-pencil-square me-1"></i>
                                                    Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="text-center py-5">
                                                <i class="bi bi-buildings display-6 text-body-secondary"></i>
                                                <h2 class="h5 mt-3 mb-1">No partner profiles found</h2>
                                                <p class="text-body-secondary mb-3">Create a partner profile or clear filters to review available records.</p>
                                                <a href="{{ route('admin.dashboard.partner-profiles.create') }}" class="btn btn-primary">
                                                    <i class="bi bi-plus-circle me-1"></i>
                                                    Create Partner
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($partnerProfiles->hasPages())
                    <div class="card-footer">
                        {{ $partnerProfiles->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
