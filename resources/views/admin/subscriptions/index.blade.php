@extends('layouts.rebuild-dashboard')

@section('title', 'Subscription & Billing')

@php
    $filters = $filters ?? [];
    $filteredTiers = $filteredTiers ?? collect();
    $subscriptionMetrics = array_merge([
        'totalPlans' => 0,
        'activePlans' => 0,
        'publicPlans' => 0,
        'adminOnlyPlans' => 0,
        'showingTo' => 0,
    ], $subscriptionMetrics ?? []);
    $subscriptionStatCards = $subscriptionStatCards ?? [];
    $tierLabel = fn ($tier): string => $tier->display_name ?: $tier->name;
    $formatMoney = fn ($value): string => '$'.number_format((float) $value, 0);
    $permissionCount = fn ($tier): int => $tier->tierPermissions?->count() ?? 0;
    $subscriptionTabBar = [
        'bar_class' => 'tab-bar subscription-tab-bar',
        'tabs' => [
            ['label' => 'All', 'count' => $subscriptionMetrics['totalPlans'], 'active' => empty($filters['status']) && empty($filters['visibility']), 'href' => route('admin.dashboard.subscriptions.index')],
            ['label' => 'Active', 'count' => $subscriptionMetrics['activePlans'], 'active' => ($filters['status'] ?? '') === 'active', 'href' => route('admin.dashboard.subscriptions.index', ['status' => 'active'])],
            ['label' => 'Inactive', 'count' => $subscriptionMetrics['totalPlans'] - $subscriptionMetrics['activePlans'], 'active' => ($filters['status'] ?? '') === 'inactive', 'href' => route('admin.dashboard.subscriptions.index', ['status' => 'inactive'])],
            ['label' => 'Public', 'count' => $subscriptionMetrics['publicPlans'], 'active' => ($filters['visibility'] ?? '') === 'public', 'href' => route('admin.dashboard.subscriptions.index', ['visibility' => 'public'])],
            ['label' => 'Admin-only', 'count' => $subscriptionMetrics['adminOnlyPlans'], 'active' => ($filters['visibility'] ?? '') === 'admin_only', 'href' => route('admin.dashboard.subscriptions.index', ['visibility' => 'admin_only'])],
        ],
    ];
@endphp

@section('content')
    @include('templates.components.alert-session')

    @if (in_array(false, $availableTables ?? [], true))
        <div class="alert alert-warning">Some subscription tables are unavailable. Tier catalog data will render where available.</div>
    @endif

    <div class="page-head">
        <div>
            <div class="page-title">Subscription Tiers</div>
            <div class="page-subtitle">Manage dynamic partner subscription tiers, pricing, billing cycles, visibility, status, and permission assignments.</div>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('admin.dashboard.subscriptions.tiers.create') }}" class="btn btn-primary"><x-admin.icon name="icon-add-another-document-clas" />Create tier</a>
        </div>
    </div>

    <x-admin.stat-cards :items="$subscriptionStatCards" />

    <div class="row g-3 align-items-center mb-3">
        <div class="col-12">
            <x-admin.tab-bar :items="$subscriptionTabBar" />
        </div>
    </div>

    <div class="table-wrap subscription-table-wrap">
        <div class="table-header">
            <form method="GET" action="{{ route('admin.dashboard.subscriptions.index') }}" class="search-form subscription-search-form">
                <div class="search-box subscription-search-box">
                    <x-admin.icon name="search" />
                    <input type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Search by tier name, code, or description">
                </div>
                <select class="filter-select" name="status">
                    <option value="">All status</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
                <select class="filter-select" name="visibility">
                    <option value="">All visibility</option>
                    <option value="public" @selected(($filters['visibility'] ?? '') === 'public')>Public</option>
                    <option value="admin_only" @selected(($filters['visibility'] ?? '') === 'admin_only')>Admin-only</option>
                </select>
                <select class="filter-select" name="billing_cycle">
                    <option value="">All billing cycles</option>
                    @foreach (['monthly', 'yearly', 'custom'] as $cycle)
                        <option value="{{ $cycle }}" @selected(($filters['billing_cycle'] ?? '') === $cycle)>{{ ucfirst($cycle) }}</option>
                    @endforeach
                </select>
                <button class="btn-outline btn-filter" type="submit"><x-admin.icon name="filter-account-acc" />Filter</button>
            </form>
            <div class="table-title-block">
                <div class="table-title">Partner subscription tiers</div>
                <div class="table-meta">Showing {{ $subscriptionMetrics['showingTo'] ? '1-'.$subscriptionMetrics['showingTo'] : '0' }} of {{ number_format($subscriptionMetrics['totalPlans']) }} tiers</div>
            </div>
        </div>
        <div class="table-scroll">
            <table class="subscription-table">
                <thead>
                    <tr><th>Tier</th><th>Description</th><th>Price</th><th>Billing</th><th>Permissions</th><th>Visibility</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($filteredTiers as $tier)
                        @php($tone = ['tier-dot-gold', 'tier-dot-diamond', 'tier-dot-platinum'][$loop->index % 3])
                        <tr>
                            <td><div class="subscription-plan-title"><span class="tier-dot {{ $tone }}"></span><div><strong>{{ $tierLabel($tier) }}</strong><span>code: {{ $tier->code }}</span></div></div></td>
                            <td><div class="subscription-description">{{ $tier->description ? Str::limit($tier->description, 120) : 'No description has been added for this tier.' }}</div></td>
                            <td><div class="subscription-price">{{ $formatMoney($tier->monthly_price) }}<span>/month</span></div></td>
                            <td><div class="subscription-plan-cell"><strong>{{ ucfirst($tier->billing_cycle) }}</strong><span>Duration days: {{ $tier->duration_days ?? 'Auto' }}</span></div></td>
                            <td><span class="badge {{ $permissionCount($tier) ? 'sub-badge-success' : 'sub-badge-warning' }}">{{ number_format($permissionCount($tier)) }} enabled</span></td>
                            <td><span class="badge {{ $tier->is_public ? 'sub-badge-success' : 'sub-badge-muted' }}">{{ $tier->is_public ? 'Public' : 'Admin-only' }}</span></td>
                            <td><span class="badge {{ $tier->is_active ? 'sub-status-active' : 'sub-status-expired' }}">{{ $tier->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td><div class="action-cell"><a href="{{ route('admin.dashboard.subscriptions.tiers.edit', $tier) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="edit-2" />View/Edit</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No subscription tiers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
