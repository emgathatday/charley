@extends('layouts.app')

@section('title', $partnerProfile->company_name)

@php
    $statusBadge = match ($partnerProfile->approval_status) {
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'suspended' => 'badge-warning',
        default => 'badge-info',
    };
@endphp

@section('content_header')
    <div class="page-header" data-source-page="partner-detail.html">
        <div>
            <div class="page-title">{{ $partnerProfile->company_name }}</div>
            <div class="page-subtitle">{{ $partnerProfile->overview ? Str::limit($partnerProfile->overview, 140) : 'Partner detail with media, products, presentations, and pivot Plant Types.' }}</div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dashboard.partner-profiles.index') }}" class="btn"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back</a>
            <a href="{{ route('admin.dashboard.partner-profiles.verification', $partnerProfile) }}" class="btn"><i class="bi bi-patch-check" aria-hidden="true"></i>Verification</a>
            <a href="{{ route('admin.dashboard.partner-profiles.edit', $partnerProfile) }}" class="btn"><i class="bi bi-pencil-square" aria-hidden="true"></i>Edit</a>
        </div>
    </div>
@endsection

@section('content')
    @include('templates.components.alert-session')

    <div class="stats-row" style="grid-template-columns:repeat(4,minmax(0,1fr));padding:0;margin-bottom:22px;">
        <div class="stat-card blue"><div class="stat-label">Company Type</div><div class="stat-value">{{ $partnerProfile->company_type ?? 'Unspecified' }}</div></div>
        <div class="stat-card emerald"><div class="stat-label">Products</div><div class="stat-value">{{ number_format($partnerProfile->products->count()) }}</div></div>
        <div class="stat-card indigo"><div class="stat-label">Presentations</div><div class="stat-value">{{ number_format($partnerProfile->presentations->count()) }}</div></div>
        <div class="stat-card amber"><div class="stat-label">Members</div><div class="stat-value">{{ number_format($partnerProfile->members->count()) }}</div></div>
    </div>

    <div class="detail-grid">
        <section class="form-card">
            <div class="form-card-header"><div class="form-card-icon blue"><i class="bi bi-building"></i></div><div><div class="form-card-title">Company Info</div><div class="form-card-sub">Partner account profile and public contact fields</div></div></div>
            <div class="form-card-body"><div class="info-grid">
                <div class="info-item"><div class="info-label">Status</div><div class="info-value"><span class="badge {{ $statusBadge }}">{{ ucfirst($partnerProfile->approval_status) }}</span></div></div>
                <div class="info-item"><div class="info-label">Plant Types</div><div class="info-value">@forelse ($partnerProfile->plantTypes as $plantType)<span class="badge {{ $plantType->pivot->is_primary ? 'badge-success' : 'badge-info' }} me-1">{{ $plantType->name }}</span>@empty - @endforelse</div></div>
                <div class="info-item"><div class="info-label">Subscription Cache</div><div class="info-value">{{ $partnerProfile->activePartnerSubscription?->tier?->display_name ?? ucfirst($partnerProfile->subscription_status) }}</div></div>
                <div class="info-item"><div class="info-label">Logo Media</div><div class="info-value">{{ $partnerProfile->logoMedia?->original_name ?? ($partnerProfile->logo_media_id ? '#'.$partnerProfile->logo_media_id : '-') }}</div></div>
                <div class="info-item"><div class="info-label">Contact</div><div class="info-value">{{ $partnerProfile->contact_email ?? '-' }}</div></div>
                <div class="info-item"><div class="info-label">Website</div><div class="info-value text-break">{{ $partnerProfile->website ?? '-' }}</div></div>
                <div class="info-item"><div class="info-label">Layout</div><div class="info-value">{{ $partnerProfile->layout_template }}</div></div>
                <div class="info-item"><div class="info-label">Feed Highlight</div><div class="info-value">{{ $partnerProfile->feed_highlight_enabled ? 'Enabled' : 'Disabled' }}</div></div>
            </div></div>
        </section>

        <section class="form-card">
            <div class="form-card-header"><div class="form-card-icon indigo"><i class="bi bi-braces"></i></div><div><div class="form-card-title">JSON Fields</div><div class="form-card-sub">Keywords and references stored as JSON arrays.</div></div></div>
            <div class="form-card-body"><div class="info-grid">
                <div class="info-item"><div class="info-label">Keywords</div><div class="info-value">@forelse (($partnerProfile->keywords ?? []) as $keyword)<span class="badge badge-info me-1">{{ $keyword }}</span>@empty<span class="text-secondary">No keywords</span>@endforelse</div></div>
                <div class="info-item"><div class="info-label">References</div><div class="info-value">{{ collect($partnerProfile->references ?? [])->pluck('project')->filter()->join(', ') ?: '-' }}</div></div>
                <div class="info-item"><div class="info-label">Verified At</div><div class="info-value">{{ $partnerProfile->verified_at?->format('Y-m-d H:i') ?? '-' }}</div></div>
                <div class="info-item"><div class="info-label">Created</div><div class="info-value">{{ $partnerProfile->created_at?->format('Y-m-d H:i') ?? '-' }}</div></div>
            </div></div>
        </section>
    </div>

    <div class="table-card" style="margin-top:20px;">
        <div class="table-header"><div><div class="table-title">Products</div><div class="table-meta">Media references use media_files IDs, not raw paths.</div></div></div>
        <div class="table-responsive"><table class="qa-table"><thead><tr><th>Name</th><th>Type</th><th>Category</th><th>Image Media</th><th>Datasheet</th><th>Status</th></tr></thead><tbody>@forelse ($partnerProfile->products as $product)<tr><td>{{ $product->name }}</td><td>{{ ucfirst($product->item_type) }}</td><td>{{ $product->category ?? '-' }}</td><td>{{ $product->imageMedia?->original_name ?? ($product->image_media_id ? '#'.$product->image_media_id : '-') }}</td><td>{{ $product->datasheetMedia?->original_name ?? ($product->datasheet_media_id ? '#'.$product->datasheet_media_id : '-') }}</td><td>{{ $product->is_active ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-4">No products.</td></tr>@endforelse</tbody></table></div>
    </div>

    <div class="detail-grid" style="margin-top:20px;">
        <div class="table-card"><div class="table-header"><div><div class="table-title">Presentations</div><div class="table-meta">Moderation status and protected download flag.</div></div></div><div class="table-responsive"><table class="qa-table"><thead><tr><th>Title</th><th>Status</th><th>Plant Type</th><th>File</th><th>Download</th></tr></thead><tbody>@forelse ($partnerProfile->presentations as $presentation)<tr><td>{{ $presentation->title }}</td><td>{{ ucfirst(str_replace('_', ' ', $presentation->status)) }}</td><td>{{ $presentation->plantType?->name ?? '-' }}</td><td>{{ $presentation->fileMedia?->original_name ?? ($presentation->file_media_id ? '#'.$presentation->file_media_id : '-') }}</td><td>{{ $presentation->download_allowed ? 'Allowed' : 'Protected' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">No presentations.</td></tr>@endforelse</tbody></table></div></div>
        <div class="table-card"><div class="table-header"><div><div class="table-title">Members</div><div class="table-meta">Partner staff roles for RBAC workflows.</div></div></div><div class="table-responsive"><table class="qa-table"><thead><tr><th>User</th><th>Role</th><th>Status</th></tr></thead><tbody>@forelse ($partnerProfile->members as $member)<tr><td>{{ $member->user?->email ?? $member->user_id }}</td><td>{{ ucfirst($member->member_role) }}</td><td>{{ ucfirst($member->status) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-secondary py-4">No members.</td></tr>@endforelse</tbody></table></div></div>
    </div>
@endsection