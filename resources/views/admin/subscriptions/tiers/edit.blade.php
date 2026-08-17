@extends('layouts.rebuild-dashboard')

@section('title', 'View/edit Subscription')

@php
    $tierLabel = $subscriptionTier->display_name ?: $subscriptionTier->name;
    $enabledPermissions = $subscriptionTier->tierPermissions?->count() ?? 0;
@endphp

@section('content')
    @include('templates.components.alert-session')

    <form method="POST" action="{{ route('admin.dashboard.subscriptions.tiers.update', $subscriptionTier) }}">
        @csrf
        @method('PUT')

        <a href="{{ route('admin.dashboard.subscriptions.index') }}" class="back-link"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-account-penalty"></use></svg>Back to Subscriptions</a>

        <div class="page-head">
            <div class="page-head-left">
                <div class="company-logo"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-billing"></use></svg></div>
                <div>
                    <div class="page-title-row"><div class="page-title">{{ $tierLabel }}</div><span class="badge {{ $subscriptionTier->is_active ? 'sub-status-active' : 'sub-status-expired' }}">{{ $subscriptionTier->is_active ? 'Active' : 'Inactive' }}</span><span class="badge {{ $subscriptionTier->is_public ? 'sub-badge-success' : 'sub-badge-muted' }}">{{ $subscriptionTier->is_public ? 'Public' : 'Admin-only' }}</span></div>
                    <div class="page-sub"><span>subscription_tiers #{{ $subscriptionTier->id }}</span><span class="sep"></span><span>code: {{ $subscriptionTier->code }}</span><span class="sep"></span><span>{{ number_format($enabledPermissions) }} permissions enabled</span></div>
                </div>
            </div>
            <div class="header-actions"><button class="btn btn-outline" type="button">Preview public card</button><button class="btn btn-primary" type="submit">Save edits</button></div>
        </div>

        <div class="detail-grid subscription-detail-grid">
            <div class="col-main">
                @include('admin.subscriptions.tiers._form', ['subscriptionTier' => $subscriptionTier])
            </div>

            <div class="col-side">
                <div class="side-card">
                    <div class="card card-padded decision-panel">
                        <div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg>Plan Controls</div></div>
                        <div class="mini-kv"><span class="mini-kv-label">Current status</span><span class="mini-kv-value">{{ $subscriptionTier->is_active ? 'Active' : 'Inactive' }}</span></div>
                        <div class="mini-kv"><span class="mini-kv-label">Visibility</span><span class="mini-kv-value">{{ $subscriptionTier->is_public ? 'Public' : 'Admin-only' }}</span></div>
                        <div class="mini-kv"><span class="mini-kv-label">Monthly price</span><span class="mini-kv-value">${{ number_format((float) $subscriptionTier->monthly_price, 0) }}</span></div>
                        <div class="mini-kv"><span class="mini-kv-label">Enabled permissions</span><span class="mini-kv-value">{{ number_format($enabledPermissions) }}</span></div>
                        <div class="decision-divider"></div>
                        <button class="btn btn-primary btn-block" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-2"></use></svg>Save changes</button>
                        <button class="btn btn-outline btn-block btn-block-spaced" type="button">Duplicate plan</button>
                        <button class="btn btn-ghost-danger btn-block btn-block-spaced" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-rejected-partner-announcement-te"></use></svg>Set inactive</button>
                    </div>

                    <div class="card card-padded">
                        <div class="verification-detail-head"><div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-shield"></use></svg>Change history</div></div>
                        <div class="timeline verification-timeline">
                            <div class="tl-item"><div class="tl-dot"></div><div class="tl-title">Plan loaded for review</div><div class="tl-time">Static timeline until audit source is assigned</div></div>
                            <div class="tl-item"><div class="tl-dot"></div><div class="tl-title">Updated at</div><div class="tl-time">{{ optional($subscriptionTier->updated_at)->format('d M Y H:i') ?? 'Not recorded' }}</div></div>
                            <div class="tl-item"><div class="tl-dot"></div><div class="tl-title">Plan created</div><div class="tl-time">{{ optional($subscriptionTier->created_at)->format('d M Y H:i') ?? 'Not recorded' }}</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection