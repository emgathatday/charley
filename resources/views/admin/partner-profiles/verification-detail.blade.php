@extends('layouts.app')

@section('title', 'Partner Verification Detail')

@php
    $statusBadge = match ($partnerProfile->approval_status) {
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'suspended' => 'badge-warning',
        default => 'badge-info',
    };
@endphp

@section('content_header')
    <div class="page-header" data-source-page="partner-verification-detail.html">
        <div>
            <div class="page-title">Partner Verification Detail</div>
            <div class="page-subtitle">{{ $partnerProfile->company_name }} - verification review with confirmed partner profile data and unresolved document bindings preserved as TODOs.</div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dashboard.partner-profiles.show', $partnerProfile) }}" class="btn"><i class="bi bi-eye" aria-hidden="true"></i>Profile</a>
            <a href="{{ route('admin.dashboard.partner-profiles.edit', $partnerProfile) }}" class="btn"><i class="bi bi-pencil-square" aria-hidden="true"></i>Edit</a>
        </div>
    </div>
@endsection

@section('content')
    @include('templates.components.alert-session')

    <div class="detail-grid">
        <section class="form-card">
            <div class="form-card-header"><div class="form-card-icon indigo"><i class="bi bi-patch-check"></i></div><div><div class="form-card-title">Review Summary</div><div class="form-card-sub">Admin approval state, company type, and Plant Type pivot selections</div></div></div>
            <div class="form-card-body"><div class="info-grid">
                <div class="info-item"><div class="info-label">Status</div><div class="info-value"><span class="badge {{ $statusBadge }}">{{ ucfirst($partnerProfile->approval_status) }}</span></div></div>
                <div class="info-item"><div class="info-label">Owner</div><div class="info-value">{{ $partnerProfile->user?->email ?? $partnerProfile->user_id }}</div></div>
                <div class="info-item"><div class="info-label">Company Type</div><div class="info-value">{{ $partnerProfile->company_type ?? 'Unspecified' }}</div></div>
                <div class="info-item"><div class="info-label">Logo Media</div><div class="info-value">{{ $partnerProfile->logoMedia?->original_name ?? ($partnerProfile->logo_media_id ? '#'.$partnerProfile->logo_media_id : '-') }}</div></div>
                <div class="info-item"><div class="info-label">Plant Types</div><div class="info-value">@forelse ($partnerProfile->plantTypes as $plantType)<span class="badge {{ $plantType->pivot->is_primary ? 'badge-success' : 'badge-info' }} me-1">{{ $plantType->name }}</span>@empty <span class="text-secondary">No Plant Types</span> @endforelse</div></div>
                <div class="info-item"><div class="info-label">References</div><div class="info-value">{{ collect($partnerProfile->references ?? [])->pluck('project')->filter()->join(', ') ?: '-' }}</div></div>
            </div></div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><div class="form-card-icon amber"><i class="bi bi-shield-check"></i></div><div><div class="form-card-title">Moderation Actions</div><div class="form-card-sub">Actions keep existing form hooks and route names</div></div></div>
            <div class="form-card-body">
                <div class="page-actions" style="justify-content:flex-start;">
                    <form method="POST" action="{{ route('admin.dashboard.partner-profiles.approve', $partnerProfile) }}" data-hook="partner-approve-form">@csrf<button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i>Approve Partner</button></form>
                    <form method="POST" action="{{ route('admin.dashboard.partner-profiles.reject', $partnerProfile) }}" data-hook="partner-reject-form">@csrf<button class="btn" type="submit"><i class="bi bi-x-circle" aria-hidden="true"></i>Reject Partner</button></form>
                </div>
                <div class="alert alert-warning mt-3" role="alert">TODO: attach verified business documents after media_files document relation is specified for partner verification.</div>
            </div>
        </section>
    </div>

    <div class="table-card" style="margin-top:20px;">
        <div class="table-header"><div><div class="table-title">Verification Evidence</div><div class="table-meta">Evidence rows bound to products, presentations, and member collections.</div></div></div>
        <div class="table-responsive"><table class="qa-table"><thead><tr><th>Area</th><th>Current Data</th><th>Review Note</th></tr></thead><tbody>
            <tr><td>Products</td><td>{{ $partnerProfile->products->count() }} catalog items</td><td>Confirm media_files IDs are present for product image and datasheet fields.</td></tr>
            <tr><td>Presentations</td><td>{{ $partnerProfile->presentations->count() }} uploaded presentations</td><td>Review status, file_media_id, and protected download_allowed behavior.</td></tr>
            <tr><td>Members</td><td>{{ $partnerProfile->members->count() }} partner members</td><td>Confirm at least one active manager role exists for partner RBAC.</td></tr>
        </tbody></table></div>
    </div>
@endsection