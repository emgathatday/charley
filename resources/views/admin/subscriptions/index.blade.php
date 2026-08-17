@extends('layouts.rebuild-dashboard')

@section('title', 'Subscription & Billing')

@php
    $filters = array_merge([
        'keyword' => request('keyword'),
        'status' => request('status'),
        'visibility' => request('visibility'),
        'billing_cycle' => request('billing_cycle'),
    ], $filters ?? []);

    $tierLabel = fn ($tier): string => $tier->display_name ?: $tier->name;
    $formatMoney = fn ($value): string => '$'.number_format((float) $value, 0);
    $permissionCount = fn ($tier): int => $tier->tierPermissions?->count() ?? 0;
    $allTiers = $tiers ?? collect();
    $filteredTiers = $allTiers
        ->when($filters['keyword'] ?? null, function ($collection, $keyword) use ($tierLabel) {
            $needle = Str::lower((string) $keyword);
            return $collection->filter(fn ($tier) => Str::contains(Str::lower($tierLabel($tier).' '.$tier->code.' '.$tier->name.' '.$tier->description), $needle));
        })
        ->when(($filters['status'] ?? null) === 'active', fn ($collection) => $collection->filter(fn ($tier) => (bool) $tier->is_active))
        ->when(($filters['status'] ?? null) === 'inactive', fn ($collection) => $collection->filter(fn ($tier) => ! (bool) $tier->is_active))
        ->when(($filters['visibility'] ?? null) === 'public', fn ($collection) => $collection->filter(fn ($tier) => (bool) $tier->is_public))
        ->when(($filters['visibility'] ?? null) === 'admin_only', fn ($collection) => $collection->filter(fn ($tier) => ! (bool) $tier->is_public))
        ->when($filters['billing_cycle'] ?? null, fn ($collection, $cycle) => $collection->filter(fn ($tier) => $tier->billing_cycle === $cycle));

    $totalPlans = $allTiers->count();
    $activePlans = $allTiers->where('is_active', true)->count();
    $publicPlans = $allTiers->where('is_public', true)->count();
    $adminOnlyPlans = $totalPlans - $publicPlans;
    $activeAvg = $allTiers->where('is_active', true)->avg('monthly_price') ?? 0;
    $showingTo = $filteredTiers->count();
    $subscriptionStatCards = [
        ['class' => 'blue', 'label' => 'Total Plans', 'value' => number_format($totalPlans), 'sub' => number_format($publicPlans) . ' public · ' . number_format($adminOnlyPlans) . ' admin-only'],
        ['class' => 'green', 'label' => 'Active Plans', 'value' => number_format($activePlans), 'sub' => 'Có thể chọn khi đăng ký'],
        ['class' => 'amber', 'label' => 'Avg Monthly Price', 'value' => $formatMoney($activeAvg), 'sub' => 'Tính từ active plans'],
        ['class' => 'blue2', 'label' => 'Permission Keys', 'value' => number_format(($subscriptionPermissions ?? collect())->count()), 'sub' => 'boolean, integer, string, json'],
    ];
@endphp

@section('content')
    @include('templates.components.alert-session')

    @if (in_array(false, $availableTables ?? [], true))
        <div class="alert alert-warning">Some subscription tables are unavailable. Plan catalog data will render where available.</div>
    @endif

    <div class="page-head">
        <div class="page-title-row">
            <div>
                <div class="page-title">Quản lý Subscriptions</div>
                <div class="page-subtitle">Quản lý các gói thành viên động như Golden, Diamond, Platinum: tên, mô tả, giá, chu kỳ, trạng thái và permission đi kèm.</div>
            </div>
            <div class="page-head-actions">
                <a href="{{ route('admin.dashboard.subscriptions.tiers.create') }}" class="btn btn-primary"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-note"></use></svg>Tạo Subscription mới</a>
            </div>
        </div>
    </div>

    <x-admin.stat-cards :items="$subscriptionStatCards" />

    <div class="row g-3 align-items-center mb-3">
        <div class="col-12">
            @php
                $subscriptionTabBar = [
                    'bar_class' => 'tab-bar subscription-tab-bar',
                    'tabs' => [
                        ['label' => 'Tất cả', 'count' => $totalPlans, 'active' => empty($filters['status']) && empty($filters['visibility']), 'href' => route('admin.dashboard.subscriptions.index')],
                        ['label' => 'Active', 'count' => $activePlans, 'active' => ($filters['status'] ?? '') === 'active', 'href' => route('admin.dashboard.subscriptions.index', ['status' => 'active'])],
                        ['label' => 'Inactive', 'count' => $totalPlans - $activePlans, 'active' => ($filters['status'] ?? '') === 'inactive', 'href' => route('admin.dashboard.subscriptions.index', ['status' => 'inactive'])],
                        ['label' => 'Public', 'count' => $publicPlans, 'active' => ($filters['visibility'] ?? '') === 'public', 'href' => route('admin.dashboard.subscriptions.index', ['visibility' => 'public'])],
                        ['label' => 'Admin-only', 'count' => $adminOnlyPlans, 'active' => ($filters['visibility'] ?? '') === 'admin_only', 'href' => route('admin.dashboard.subscriptions.index', ['visibility' => 'admin_only'])],
                    ],
                ];
            @endphp
            <x-admin.tab-bar :items="$subscriptionTabBar" />
        </div>
    </div>

    <div class="table-wrap subscription-table-wrap">
        <div class="table-header">
            <form method="GET" action="{{ route('admin.dashboard.subscriptions.index') }}" class="search-form subscription-search-form">
                <div class="search-box subscription-search-box">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-k-overview-a-href-admin-d"></use></svg>
                    <input type="text" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Tìm tên gói, code, mô tả...">
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
                        <option value="{{ $cycle }}" @selected(($filters['billing_cycle'] ?? '') === $cycle)>{{ $cycle }}</option>
                    @endforeach
                </select>
                <button class="btn-outline btn-filter" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter-account-acc"></use></svg>Filter</button>
            </form>
            <div class="table-title-block">
                <div class="table-title">Subscription Plans</div>
                <div class="table-meta">Showing {{ $showingTo ? '1-'.$showingTo : '0' }} of {{ number_format($totalPlans) }} subscription tiers</div>
            </div>
        </div>
        <div class="table-scroll">
            <table class="subscription-table">
                <thead>
                    <tr><th>Plan</th><th>Description</th><th>Price</th><th>Billing</th><th>Permissions</th><th>Visibility</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($filteredTiers as $tier)
                        @php
                            $tone = ['tier-dot-gold', 'tier-dot-diamond', 'tier-dot-platinum'][$loop->index % 3];
                        @endphp
                        <tr>
                            <td><div class="subscription-plan-title"><span class="tier-dot {{ $tone }}"></span><div><strong>{{ $tierLabel($tier) }}</strong><span>code: {{ $tier->code }}</span></div></div></td>
                            <td><div class="subscription-description">{{ $tier->description ? Str::limit($tier->description, 120) : 'No description has been added for this plan.' }}</div></td>
                            <td><div class="subscription-price">{{ $formatMoney($tier->monthly_price) }}<span>/month</span></div></td>
                            <td><div class="subscription-plan-cell"><strong>{{ $tier->billing_cycle }}</strong><span>duration_days: {{ $tier->duration_days ?? 'null' }}</span></div></td>
                            <td><span class="badge {{ $permissionCount($tier) ? 'sub-badge-success' : 'sub-badge-warning' }}">{{ number_format($permissionCount($tier)) }} enabled</span></td>
                            <td><span class="badge {{ $tier->is_public ? 'sub-badge-success' : 'sub-badge-muted' }}">{{ $tier->is_public ? 'Public' : 'Admin-only' }}</span></td>
                            <td><span class="badge {{ $tier->is_active ? 'sub-status-active' : 'sub-status-expired' }}">{{ $tier->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td><div class="action-cell"><a href="{{ route('admin.dashboard.subscriptions.tiers.edit', $tier) }}" class="btn btn-ghost btn-sm">View/Edit</a><button class="btn btn-ghost btn-sm {{ $tier->is_active ? 'ann-delete-btn' : '' }}" type="button">{{ $tier->is_active ? 'Inactive' : 'Active' }}</button></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No subscription plans found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">
            <span class="page-info">Showing {{ $showingTo ? '1-'.$showingTo : '0' }} of {{ number_format($filteredTiers->count()) }} results</span>
        </div>
    </div>
@endsection