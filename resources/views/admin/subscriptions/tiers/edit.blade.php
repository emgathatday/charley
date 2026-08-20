@extends('layouts.rebuild-dashboard')

@section('title', 'Edit Subscription Tier')

@php
    $tierLabel = $tierLabel ?? ($subscriptionTier->display_name ?: $subscriptionTier->name);
    $enabledPermissions = $enabledPermissions ?? ($subscriptionTier->tierPermissions?->count() ?? 0);
@endphp

@section('content')
    @include('templates.components.alert-session')

    <form method="POST" action="{{ route('admin.dashboard.subscriptions.tiers.update', $subscriptionTier) }}">
        @csrf
        @method('PUT')

        <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="back-link"><x-admin.icon name="back-account-penalty" />Back to Subscriptions</a>

        <div class="page-head">
            <div class="page-head-left">
                <div class="company-logo"><x-admin.icon name="billing" /></div>
                <div>
                    <div class="page-title-row"><div class="page-title">{{ $tierLabel }}</div><span class="badge {{ $subscriptionTier->is_active ? 'sub-status-active' : 'sub-status-expired' }}">{{ $subscriptionTier->is_active ? 'Active' : 'Inactive' }}</span><span class="badge {{ $subscriptionTier->is_public ? 'sub-badge-success' : 'sub-badge-muted' }}">{{ $subscriptionTier->is_public ? 'Public' : 'Admin-only' }}</span></div>
                    <div class="page-sub"><span>subscription_tiers #{{ $subscriptionTier->id }}</span><span class="sep"></span><span>code: {{ $subscriptionTier->code }}</span><span class="sep"></span><span>{{ number_format($enabledPermissions) }} permissions enabled</span></div>
                </div>
            </div>
            <div class="header-actions"><button class="btn btn-primary" type="submit"><x-admin.icon name="save-2" />Save changes</button></div>
        </div>

        <div class="detail-grid subscription-detail-grid">
            <div class="col-main">
                @include('admin.subscriptions.tiers._form', ['subscriptionTier' => $subscriptionTier])
            </div>

            <div class="col-side">
                <div class="side-card">
                    <div class="card card-padded decision-panel">
                        <div class="verification-detail-head"><div class="card-title"><x-admin.icon name="verification-queue" />Tier controls</div></div>
                        <div class="mini-kv"><span class="mini-kv-label">Current status</span><span class="mini-kv-value">{{ $subscriptionTier->is_active ? 'Active' : 'Inactive' }}</span></div>
                        <div class="mini-kv"><span class="mini-kv-label">Visibility</span><span class="mini-kv-value">{{ $subscriptionTier->is_public ? 'Public' : 'Admin-only' }}</span></div>
                        <div class="mini-kv"><span class="mini-kv-label">Monthly price</span><span class="mini-kv-value">${{ number_format((float) $subscriptionTier->monthly_price, 0) }}</span></div>
                        <div class="mini-kv"><span class="mini-kv-label">Enabled permissions</span><span class="mini-kv-value">{{ number_format($enabledPermissions) }}</span></div>
                        <div class="decision-divider"></div>
                        <button class="btn btn-primary btn-block" type="submit"><x-admin.icon name="save-2" />Save changes</button>
                    </div>

                    <div class="card card-padded">
                        <div class="verification-detail-head"><div class="card-title"><x-admin.icon name="shield" />Tier history</div></div>
                        <div class="timeline verification-timeline">
                            <div class="tl-item"><div class="tl-dot"></div><div class="tl-title">Tier loaded for review</div><div class="tl-time">Ready for admin edits</div></div>
                            <div class="tl-item"><div class="tl-dot"></div><div class="tl-title">Updated at</div><div class="tl-time">{{ optional($subscriptionTier->updated_at)->format('d M Y H:i') ?? 'Not recorded' }}</div></div>
                            <div class="tl-item"><div class="tl-dot"></div><div class="tl-title">Tier created</div><div class="tl-time">{{ optional($subscriptionTier->created_at)->format('d M Y H:i') ?? 'Not recorded' }}</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
