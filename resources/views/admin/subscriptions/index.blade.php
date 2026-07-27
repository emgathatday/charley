@extends('layouts.app')

@section('title', 'Subscription & Billing')

@php
    $subscriptionStatuses = ['pending_approval', 'active', 'expired', 'cancelled', 'rejected'];
    $paymentStatuses = ['pending', 'approved', 'rejected', 'refunded'];
    $statusClass = fn ($status) => match ((string) $status) {
        'active', 'approved' => 'status-active',
        'pending', 'pending_approval' => 'status-pending',
        'expired', 'cancelled', 'rejected', 'refunded' => 'status-suspended',
        default => 'status-pending',
    };
    $statusLabel = fn ($status) => ucfirst(str_replace('_', ' ', (string) $status));
    $billingLabel = fn ($tier) => ucfirst((string) $tier->billing_cycle).($tier->duration_days ? ' / '.$tier->duration_days.' days' : '');
    $tierName = fn ($tier) => $tier->display_name ?: $tier->name;
@endphp

@section('content')
    <div class="subscriptions-admin">
    <div class="page-head">
        <div>
            <h1>Subscription &amp; Billing</h1>
            <p>Manage dynamic partner tiers, permission access, payment review, approvals, and quota usage from Module 04 data.</p>
        </div>
        <div class="page-head-actions">
            <a class="btn-primary" href="{{ route('admin.dashboard.subscriptions.tiers.create') }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-partner-manually-div-class-stat-icon"></use></svg>Create Tier</a>
            <a class="btn-secondary" href="{{ route('admin.dashboard.subscriptions.member-plans.create') }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg>Member Plan</a>
        </div>
    </div>

    @include('templates.components.alert-session')

    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please review the highlighted fields.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (in_array(false, $availableTables, true))
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-9-verifications-exceeded-the-48h"></use></svg>
            <span>Some subscription tables are unavailable. Sections with live data will continue to render.</span>
        </div>
    @endif

    <div class="stats-row columns-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#FFFBEB;color:#B45309;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-9-verifications-exceeded-the-48h"></use></svg></div>
            <div class="stat-value">{{ number_format($stats['pending_approvals']) }}</div>
            <div class="stat-label">Pending Approvals</div>
            <div class="stat-trend" style="color:#B45309;">Partner subscriptions</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#ECFDF5;color:#059669;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-this-month-svg-viewbox-0"></use></svg></div>
            <div class="stat-value">{{ number_format($stats['active_partner_subscriptions']) }}</div>
            <div class="stat-label">Active Partners</div>
            <div class="stat-trend" style="color:#059669;">Live access</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#EFF6FF;color:#3B82F6;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg></div>
            <div class="stat-value">{{ number_format($stats['pending_payments']) }}</div>
            <div class="stat-label">Pending Payments</div>
            <div class="stat-trend" style="color:#3B82F6;">Awaiting review</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#F1F5F9;color:#475569;"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-dashboard-path-d-m14-5-17h9-5a2-2"></use></svg></div>
            <div class="stat-value">{{ number_format($stats['quota_periods']) }}</div>
            <div class="stat-label">Quota Periods</div>
            <div class="stat-trend" style="color:var(--ink-faint);">Usage counters</div>
        </div>
    </div>

    <div class="section-label"><h2>Partner tier catalog</h2><span>{{ number_format($tiers->count()) }} dynamic tiers</span></div>
    <div class="table-card">
        <table>
            <thead><tr><th>Tier</th><th>Billing</th><th>Visibility</th><th>Permissions</th><th>Partners</th><th style="text-align:right;">Actions</th></tr></thead>
            <tbody>
                @forelse ($tiers as $tier)
                    <tr>
                        <td><div class="company-cell"><div class="company-logo" style="background:linear-gradient(135deg,#0EA5E9,#4F46E5);">{{ strtoupper(substr($tierName($tier), 0, 2)) }}</div><div><div class="company-name">{{ $tierName($tier) }}</div><div class="company-meta"><code>{{ $tier->code }}</code>{{ $tier->description ? ' - '.Str::limit($tier->description, 70) : '' }}</div></div></div></td>
                        <td><div class="company-name">{{ number_format((float) $tier->monthly_price, 2) }}</div><div class="company-meta">{{ $billingLabel($tier) }}</div></td>
                        <td><span class="status-pill {{ $tier->is_active ? 'status-active' : 'status-suspended' }}"><span class="dot"></span>{{ $tier->is_active ? 'Active' : 'Inactive' }}</span><span class="tier-badge" style="margin-left:6px;">{{ $tier->is_public ? 'Public' : 'Private' }}</span></td>
                        <td><span class="tier-badge"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg>{{ number_format($tier->tierPermissions->count()) }} configured</span></td>
                        <td>{{ number_format($tier->partner_subscriptions_count ?? 0) }}</td>
                        <td><div class="row-actions" style="justify-content:flex-end;"><a class="action-btn" href="{{ route('admin.dashboard.subscriptions.tiers.edit', $tier) }}"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-profile-r"></use></svg></a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><span>No partner tiers found.</span></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-label" style="margin-top:30px;"><h2>Permission catalog</h2><span>{{ number_format($subscriptionPermissions->count()) }} permissions</span></div>
    <div class="table-card">
        <table>
            <thead><tr><th>Permission</th><th>Module</th><th>Value type</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($subscriptionPermissions as $permission)
                    <tr>
                        <td><div class="company-name">{{ $permission->name }}</div><div class="company-meta"><code>{{ $permission->key }}</code>{{ $permission->description ? ' - '.Str::limit($permission->description, 80) : '' }}</div></td>
                        <td>{{ $permission->module ?: 'General' }}</td>
                        <td><span class="tier-badge">{{ $permission->value_type }}</span></td>
                        <td><span class="status-pill {{ $permission->is_active ? 'status-active' : 'status-suspended' }}"><span class="dot"></span>{{ $permission->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state"><span>No subscription permissions found.</span></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-label" style="margin-top:30px;"><h2>Partner subscriptions</h2><span>Approvals and active access</span></div>
    <form class="toolbar" method="GET" action="{{ route('admin.dashboard.subscriptions.index') }}">
        <div class="tabs">
            <a class="tab {{ empty($filters['subscription_status']) ? 'active' : '' }}" href="{{ route('admin.dashboard.subscriptions.index') }}">All</a>
            @foreach ($subscriptionStatuses as $status)
                <a class="tab {{ ($filters['subscription_status'] ?? '') === $status ? 'active' : '' }}" href="{{ route('admin.dashboard.subscriptions.index', ['subscription_status' => $status, 'payment_status' => $filters['payment_status'] ?? '', 'quota_period' => $filters['quota_period'] ?? '']) }}">{{ $statusLabel($status) }}</a>
            @endforeach
        </div>
    </form>
    <div class="table-card">
        <table>
            <thead><tr><th>Partner</th><th>Tier</th><th>Status</th><th>Period</th><th>Renew</th><th>Payments</th><th style="text-align:right;">Approval</th></tr></thead>
            <tbody>
                @forelse ($partnerSubscriptions as $subscription)
                    <tr>
                        <td>{{ $subscription->user?->email ?? 'User #'.$subscription->user_id }}</td>
                        <td>{{ $subscription->tier?->display_name ?? $subscription->tier?->name ?? 'Tier #'.$subscription->tier_id }}</td>
                        <td><span class="status-pill {{ $statusClass($subscription->status) }}"><span class="dot"></span>{{ $statusLabel($subscription->status) }}</span></td>
                        <td><div>{{ $subscription->starts_at?->format('M j, Y') ?? 'Not started' }}</div><div class="company-meta">{{ $subscription->ends_at?->format('M j, Y') ?? 'No end date' }}</div></td>
                        <td>{{ $subscription->auto_renew ? 'Yes' : 'No' }}</td>
                        <td>{{ number_format($subscription->payments->count()) }}</td>
                        <td><div class="row-actions" style="justify-content:flex-end;">
                            @if ($subscription->status === 'pending_approval')
                                <form method="POST" action="{{ route('admin.dashboard.subscriptions.partner-subscriptions.approve', $subscription) }}">@csrf<button class="action-btn" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-this-month-svg-viewbox-0"></use></svg></button></form>
                            @endif
                            @if (! in_array($subscription->status, ['cancelled', 'rejected'], true))
                                <form method="POST" action="{{ route('admin.dashboard.subscriptions.partner-subscriptions.cancel', $subscription) }}">@csrf<button class="action-btn danger" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg></button></form>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><span>No partner subscriptions found.</span></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-label" style="margin-top:30px;"><h2>Payments</h2><span>Bank transfer proof from media files</span></div>
    <form class="toolbar" method="GET" action="{{ route('admin.dashboard.subscriptions.index') }}">
        <input type="hidden" name="subscription_status" value="{{ $filters['subscription_status'] ?? '' }}">
        <div class="tabs">
            <a class="tab {{ empty($filters['payment_status']) ? 'active' : '' }}" href="{{ route('admin.dashboard.subscriptions.index', ['subscription_status' => $filters['subscription_status'] ?? '', 'quota_period' => $filters['quota_period'] ?? '']) }}">All</a>
            @foreach ($paymentStatuses as $status)
                <a class="tab {{ ($filters['payment_status'] ?? '') === $status ? 'active' : '' }}" href="{{ route('admin.dashboard.subscriptions.index', ['payment_status' => $status, 'subscription_status' => $filters['subscription_status'] ?? '', 'quota_period' => $filters['quota_period'] ?? '']) }}">{{ $statusLabel($status) }}</a>
            @endforeach
        </div>
    </form>
    <div class="table-card">
        <table>
            <thead><tr><th>Subscription</th><th>Amount</th><th>Method</th><th>Period</th><th>Payment proof</th><th>Status</th><th style="text-align:right;">Review</th></tr></thead>
            <tbody>
                @forelse ($subscriptionPayments as $payment)
                    <tr>
                        <td>#{{ $payment->partner_subscription_id }} {{ $payment->partnerSubscription?->user?->email }}</td>
                        <td>{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($payment->payment_method)) }}</td>
                        <td>{{ $payment->period_start?->format('M j, Y') ?? '-' }} - {{ $payment->period_end?->format('M j, Y') ?? '-' }}</td>
                        <td>{{ $payment->paymentProofMedia?->original_name ?? ($payment->payment_proof_media_id ? 'Media #'.$payment->payment_proof_media_id : 'None') }}</td>
                        <td><span class="status-pill {{ $statusClass($payment->status) }}"><span class="dot"></span>{{ $statusLabel($payment->status) }}</span></td>
                        <td><div class="row-actions" style="justify-content:flex-end;">
                            @if ($payment->status === 'pending')
                                <form method="POST" action="{{ route('admin.dashboard.subscriptions.payments.approve', $payment) }}">@csrf<button class="action-btn" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-5-this-month-svg-viewbox-0"></use></svg></button></form>
                                <form method="POST" action="{{ route('admin.dashboard.subscriptions.payments.reject', $payment) }}">@csrf<button class="action-btn danger" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg></button></form>
                            @else
                                <span class="company-meta">Reviewed</span>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><span>No payments found.</span></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-label" style="margin-top:30px;"><h2>Usage counters</h2><span>Quota usage by permission and period</span></div>
    <form class="toolbar" method="GET" action="{{ route('admin.dashboard.subscriptions.index') }}">
        <input type="hidden" name="subscription_status" value="{{ $filters['subscription_status'] ?? '' }}">
        <input type="hidden" name="payment_status" value="{{ $filters['payment_status'] ?? '' }}">
        <div class="search-box"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-k-overview-a-href-admin-d"></use></svg><input type="text" name="quota_period" value="{{ $filters['quota_period'] ?? '' }}" placeholder="Filter period, e.g. 2026-07"></div>
        <button class="btn-secondary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter-account-acc"></use></svg>Filter</button>
    </form>
    <div class="table-card">
        <table>
            <thead><tr><th>Partner</th><th>Permission</th><th>Period</th><th>Usage</th><th>Reset</th></tr></thead>
            <tbody>
                @forelse ($subscriptionUsageCounters as $counter)
                    <tr>
                        <td>{{ $counter->partnerSubscription?->user?->email ?? 'Subscription #'.$counter->partner_subscription_id }}</td>
                        <td><code>{{ $counter->permission?->key ?? 'Permission #'.$counter->permission_id }}</code></td>
                        <td>{{ $counter->period }}</td>
                        <td>{{ number_format($counter->used_count) }} / {{ $counter->quota_limit === -1 ? 'Unlimited' : number_format($counter->quota_limit) }}</td>
                        <td>{{ $counter->reset_at?->format('M j, Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><span>No usage counters found.</span></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($announcementQuotas->isNotEmpty())
        <div class="section-label" style="margin-top:30px;"><h2>Legacy announcement quotas</h2><span>Compatibility data</span></div>
        <div class="table-card">
            <table>
                <thead><tr><th>User</th><th>Period</th><th>Used</th><th>Limit</th></tr></thead>
                <tbody>
                    @foreach ($announcementQuotas as $quota)
                        <tr><td>{{ $quota->user?->email ?? 'User #'.$quota->user_id }}</td><td>{{ $quota->period }}</td><td>{{ number_format($quota->used_count) }}</td><td>{{ number_format($quota->quota_limit) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    </div>
@endsection
@push('styles')
<style>
.subscriptions-admin .table-card { overflow-x: auto; }
.subscriptions-admin .table-card table { min-width: 920px; }
.subscriptions-admin .row-actions form { display: inline-flex; margin: 0; }
.subscriptions-admin .status-pill, .subscriptions-admin .tier-badge { white-space: nowrap; }
.subscriptions-admin .company-cell { min-width: 220px; }
.subscriptions-admin .company-meta code { font-size: inherit; }
.subscriptions-admin .toolbar { align-items: center; }
.subscriptions-admin .tabs { flex-wrap: wrap; }
.subscriptions-admin .search-box { max-width: 360px; }
@media (max-width: 768px) {
    .subscriptions-admin .page-head { align-items: flex-start; }
    .subscriptions-admin .page-head-actions { width: 100%; }
    .subscriptions-admin .page-head-actions a { justify-content: center; flex: 1; }
    .subscriptions-admin .search-box { max-width: none; width: 100%; }
}
</style>
@endpush