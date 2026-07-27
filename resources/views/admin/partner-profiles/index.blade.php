@extends('layouts.app')

@section('title', 'Partner Profiles')

@php
    $visibleProfiles = $partnerProfiles->getCollection();
    $statusBadge = fn (string $status) => match ($status) {
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'suspended' => 'badge-warning',
        default => 'badge-info',
    };
@endphp

@section('content_header')
    <div class="page-header" data-source-page="partner-management.html">
        <div>
            <div class="page-title">Partner Management</div>
            <div class="page-subtitle">Review company type, media IDs, subscription cache, and Plant Type pivot filtering.</div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dashboard.partner-profiles.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle" aria-hidden="true"></i>Add Partner</a>
        </div>
    </div>
@endsection

@section('content')
    @include('templates.components.alert-session')

    <div class="stats-row" style="grid-template-columns:repeat(4,minmax(0,1fr));padding:0;margin-bottom:22px;">
        <div class="stat-card blue"><div class="stat-label">Visible Partners</div><div class="stat-value">{{ number_format($stats['total']) }}</div></div>
        <div class="stat-card emerald"><div class="stat-label">Approved</div><div class="stat-value">{{ number_format($stats['approved']) }}</div></div>
        <div class="stat-card amber"><div class="stat-label">Pending</div><div class="stat-value">{{ number_format($stats['pending']) }}</div></div>
        <div class="stat-card indigo"><div class="stat-label">Plant Pivot Links</div><div class="stat-value">{{ number_format($stats['plant_pivot_links']) }}</div></div>
    </div>

    <form class="filter-bar" method="GET" action="{{ route('admin.dashboard.partner-profiles.index') }}" data-hook="partner-management-filters">
        <div class="filter-search"><i class="bi bi-search"></i><input id="search" name="search" value="{{ request('search') }}" placeholder="Company, country, type"></div>
        <select id="approval_status" class="filter-select" name="approval_status"><option value="">All statuses</option>@foreach (['pending','approved','rejected','suspended'] as $status)<option value="{{ $status }}" @selected(request('approval_status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
        <select id="company_type" class="filter-select" name="company_type"><option value="">All company types</option>@foreach ($companyTypes as $companyType)<option value="{{ $companyType }}" @selected(request('company_type') === $companyType)>{{ $companyType }}</option>@endforeach</select>
        <select id="plant_type_id" class="filter-select" name="plant_type_id"><option value="">All plant types</option>@foreach ($plantTypes as $plantType)<option value="{{ $plantType->id }}" @selected((string) request('plant_type_id') === (string) $plantType->id)>{{ $plantType->name }}</option>@endforeach</select>
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Filter</button>
        <a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="btn" aria-label="Reset filters"><i class="bi bi-x-lg" aria-hidden="true"></i>Reset</a>
    </form>

    <div class="table-card">
        <div class="table-header"><div><div class="table-title">Partner Queue</div><div class="table-meta">{{ number_format($partnerProfiles->total()) }} filtered records</div></div></div>
        <div class="table-responsive">
            <table class="qa-table">
                <thead><tr><th>Company</th><th>Company Type</th><th>Plant Types</th><th>Subscription</th><th>Status</th><th class="text-center">Products</th><th class="text-center">Presentations</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($partnerProfiles as $partnerProfile)
                        <tr>
                            <td><div class="fw-semibold">{{ $partnerProfile->company_name }}</div><div class="small text-secondary">{{ $partnerProfile->contact_email ?? 'No contact email' }}</div></td>
                            <td><span class="badge badge-info">{{ $partnerProfile->company_type ?? 'Unspecified' }}</span></td>
                            <td>@forelse ($partnerProfile->plantTypes as $plantType)<span class="badge {{ $plantType->pivot->is_primary ? 'badge-success' : 'badge-info' }} me-1">{{ $plantType->name }}</span>@empty<span class="text-secondary">No Plant Types</span>@endforelse</td>
                            <td>{{ $partnerProfile->activePartnerSubscription?->tier?->display_name ?? ucfirst($partnerProfile->subscription_status) }}</td>
                            <td><span class="badge {{ $statusBadge($partnerProfile->approval_status) }}">{{ ucfirst($partnerProfile->approval_status) }}</span></td>
                            <td class="text-center">{{ $partnerProfile->products_count }}</td>
                            <td class="text-center">{{ $partnerProfile->presentations_count }}</td>
                            <td class="text-end"><div class="d-flex justify-content-end gap-2"><a href="{{ route('admin.dashboard.partner-profiles.show', $partnerProfile) }}" class="btn"><i class="bi bi-eye" aria-hidden="true"></i>View</a><a href="{{ route('admin.dashboard.partner-profiles.verification', $partnerProfile) }}" class="btn"><i class="bi bi-patch-check" aria-hidden="true"></i>Verify</a><a href="{{ route('admin.dashboard.partner-profiles.edit', $partnerProfile) }}" class="btn"><i class="bi bi-pencil-square" aria-hidden="true"></i>Edit</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4">No partner profiles found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-foot"><span class="table-foot-info">Showing {{ $partnerProfiles->firstItem() ?? 0 }}-{{ $partnerProfiles->lastItem() ?? 0 }} of {{ number_format($partnerProfiles->total()) }}</span>{{ $partnerProfiles->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection