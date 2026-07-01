@extends('layouts.master')

@section('title', 'Subscriptions')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">Subscriptions</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Subscriptions</li>
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

            @if (in_array(false, $availableTables, true))
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Subscription database tables are not fully available. The UI will populate after migrations run.</span>
                </div>
            @endif

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <p class="text-body-secondary mb-0">Manage partner tiers, member plans, payments, approvals, and announcement quotas.</p>
                <div class="btn-group">
                    <a href="{{ route('admin.dashboard.subscriptions.tiers.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        Create Tier
                    </a>
                    <a href="{{ route('admin.dashboard.subscriptions.member-plans.create') }}" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        Create Member Plan
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-lg-3 col-sm-6">
                    <div class="info-box shadow-sm mb-0">
                        <span class="info-box-icon text-bg-info"><i class="bi bi-hourglass-split"></i></span>
                        <div class="info-box-content"><span class="info-box-text">Pending approvals</span><span class="info-box-number">{{ $stats['pending_approvals'] }}</span></div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="info-box shadow-sm mb-0">
                        <span class="info-box-icon text-bg-success"><i class="bi bi-check2-circle"></i></span>
                        <div class="info-box-content"><span class="info-box-text">Active partners</span><span class="info-box-number">{{ $stats['active_partner_subscriptions'] }}</span></div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="info-box shadow-sm mb-0">
                        <span class="info-box-icon text-bg-warning"><i class="bi bi-receipt"></i></span>
                        <div class="info-box-content"><span class="info-box-text">Pending payments</span><span class="info-box-number">{{ $stats['pending_payments'] }}</span></div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="info-box shadow-sm mb-0">
                        <span class="info-box-icon text-bg-secondary"><i class="bi bi-calendar3"></i></span>
                        <div class="info-box-content"><span class="info-box-text">Quota periods</span><span class="info-box-number">{{ $stats['quota_periods'] }}</span></div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-xl-6">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Partner tiers</h3>
                            <span class="badge text-bg-light">{{ $tiers->count() }} total</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>Name</th><th>Monthly price</th><th>AI limit</th><th>Announcement</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                                    <tbody>
                                        @forelse ($tiers as $tier)
                                            <tr>
                                                <td class="fw-semibold">{{ ucfirst($tier->name) }}</td>
                                                <td>{{ number_format((float) $tier->monthly_price, 2) }}</td>
                                                <td>{{ $tier->ai_monthly_limit === -1 ? 'Unlimited' : $tier->ai_monthly_limit }}</td>
                                                <td>{{ ucfirst($tier->announcement_frequency) }} / {{ $tier->announcement_limit }}</td>
                                                <td><span class="badge {{ $tier->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $tier->is_active ? 'Active' : 'Inactive' }}</span></td>
                                                <td class="text-end"><a href="{{ route('admin.dashboard.subscriptions.tiers.edit', $tier) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-body-secondary py-4">No tiers found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Member plans</h3>
                            <span class="badge text-bg-light">{{ $memberPlans->count() }} total</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>Name</th><th>Display name</th><th>Monthly price</th><th>AI limit</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                                    <tbody>
                                        @forelse ($memberPlans as $plan)
                                            <tr>
                                                <td><code>{{ $plan->name }}</code></td>
                                                <td class="fw-semibold">{{ $plan->display_name }}</td>
                                                <td>{{ number_format((float) $plan->monthly_price, 2) }}</td>
                                                <td>{{ $plan->ai_monthly_limit === -1 ? 'Unlimited' : $plan->ai_monthly_limit }}</td>
                                                <td><span class="badge {{ $plan->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span></td>
                                                <td class="text-end"><a href="{{ route('admin.dashboard.subscriptions.member-plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-body-secondary py-4">No member plans found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary mt-3">
                <div class="card-header">
                    <form method="GET" action="{{ route('admin.dashboard.subscriptions.index') }}" class="row g-2 align-items-center">
                        <div class="col"><h3 class="card-title mb-0">Partner Subscription Approvals</h3></div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" name="subscription_status" onchange="this.form.submit()">
                                <option value="">All statuses</option>
                                @foreach (['pending_approval', 'active', 'expired', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected(($filters['subscription_status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>User</th><th>Tier</th><th>Status</th><th>Starts</th><th>Ends</th><th class="text-end">Approval</th></tr></thead>
                            <tbody>
                                @forelse ($partnerSubscriptions as $subscription)
                                    <tr>
                                        <td>{{ $subscription->user?->email ?? 'User #'.$subscription->user_id }}</td>
                                        <td>{{ $subscription->tier?->name ? ucfirst($subscription->tier->name) : 'Tier #'.$subscription->tier_id }}</td>
                                        <td><span class="badge text-bg-info">{{ ucfirst(str_replace('_', ' ', $subscription->status)) }}</span></td>
                                        <td>{{ optional($subscription->starts_at)->format('Y-m-d') }}</td>
                                        <td>{{ optional($subscription->ends_at)->format('Y-m-d') }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.dashboard.subscriptions.partner-subscriptions.approve', $subscription) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i>Approve</button></form>
                                            <form method="POST" action="{{ route('admin.dashboard.subscriptions.partner-subscriptions.cancel', $subscription) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancel</button></form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-body-secondary py-4">No subscriptions found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-xl-7">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-header">
                            <form method="GET" action="{{ route('admin.dashboard.subscriptions.index') }}" class="row g-2 align-items-center">
                                <div class="col"><h3 class="card-title mb-0">Subscription payments</h3></div>
                                <div class="col-md-4">
                                    <select class="form-select form-select-sm" name="payment_status" onchange="this.form.submit()">
                                        <option value="">All statuses</option>
                                        @foreach (['pending', 'approved', 'rejected'] as $status)
                                            <option value="{{ $status }}" @selected(($filters['payment_status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>Subscription</th><th>Amount</th><th>Method</th><th>Proof media</th><th>Status</th><th class="text-end">Review</th></tr></thead>
                                    <tbody>
                                        @forelse ($subscriptionPayments as $payment)
                                            <tr>
                                                <td>#{{ $payment->partner_subscription_id }} {{ $payment->partnerSubscription?->user?->email }}</td>
                                                <td>{{ number_format((float) $payment->amount, 2) }}</td>
                                                <td>{{ str_replace('_', ' ', ucfirst($payment->payment_method)) }}</td>
                                                <td>{{ $payment->paymentProofMedia?->original_name ?? ($payment->payment_proof_media_id ? 'Media #'.$payment->payment_proof_media_id : 'None') }}</td>
                                                <td><span class="badge text-bg-info">{{ ucfirst($payment->status) }}</span></td>
                                                <td class="text-end">
                                                    <form method="POST" action="{{ route('admin.dashboard.subscriptions.payments.approve', $payment) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i>Approve</button></form>
                                                    <form method="POST" action="{{ route('admin.dashboard.subscriptions.payments.reject', $payment) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Reject</button></form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-body-secondary py-4">No payments found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-header">
                            <form method="GET" action="{{ route('admin.dashboard.subscriptions.index') }}" class="row g-2 align-items-center">
                                <div class="col"><h3 class="card-title mb-0">Announcement quotas</h3></div>
                                <div class="col-md-5"><input type="text" class="form-control form-control-sm" name="quota_period" value="{{ $filters['quota_period'] ?? '' }}" placeholder="YYYY-MM"></div>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>User</th><th>Period</th><th>Used</th><th>Limit</th></tr></thead>
                                    <tbody>
                                        @forelse ($announcementQuotas as $quota)
                                            <tr><td>{{ $quota->user?->email ?? 'User #'.$quota->user_id }}</td><td>{{ $quota->period }}</td><td>{{ $quota->used_count }}</td><td>{{ $quota->quota_limit }}</td></tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-body-secondary py-4">No quotas found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

