@extends('layouts.master')

@section('title', $partnerProfile->company_name)

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">Partner Detail</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.partner-profiles.index') }}">Partner Profiles</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $partnerProfile->company_name }}</li>
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
                <div>
                    <h2 class="h4 mb-1">{{ $partnerProfile->company_name }}</h2>
                    <p class="text-body-secondary mb-0">{{ $partnerProfile->overview ? Str::limit($partnerProfile->overview, 130) : 'No overview provided.' }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.dashboard.partner-profiles.edit', $partnerProfile) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil-square me-1"></i>
                        Edit
                    </a>
                    <form method="POST" action="{{ route('admin.dashboard.partner-profiles.approve', $partnerProfile) }}">
                        @csrf
                        <button class="btn btn-success" type="submit">
                            <i class="bi bi-check2-circle me-1"></i>
                            Approve
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.dashboard.partner-profiles.reject', $partnerProfile) }}">
                        @csrf
                        <button class="btn btn-outline-danger" type="submit">
                            <i class="bi bi-x-circle me-1"></i>
                            Reject
                        </button>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card card-outline card-primary h-100">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Company</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-5">Tier</dt>
                                <dd class="col-7">{{ $partnerProfile->partner_tier ? ucfirst($partnerProfile->partner_tier) : '-' }}</dd>
                                <dt class="col-5">Plant Type</dt>
                                <dd class="col-7">{{ $partnerProfile->plantType?->name ?? '-' }}</dd>
                                <dt class="col-5">Status</dt>
                                <dd class="col-7">
                                    <span class="badge @class([
                                        'text-bg-warning' => $partnerProfile->approval_status === 'pending',
                                        'text-bg-success' => $partnerProfile->approval_status === 'approved',
                                        'text-bg-danger' => $partnerProfile->approval_status === 'rejected',
                                        'text-bg-secondary' => $partnerProfile->approval_status === 'suspended',
                                    ])">{{ ucfirst($partnerProfile->approval_status) }}</span>
                                </dd>
                                <dt class="col-5">Contact</dt>
                                <dd class="col-7">{{ $partnerProfile->contact_email ?? '-' }}</dd>
                                <dt class="col-5">Phone</dt>
                                <dd class="col-7">{{ $partnerProfile->phone ?? '-' }}</dd>
                                <dt class="col-5">Country</dt>
                                <dd class="col-7">{{ $partnerProfile->country ?? '-' }}</dd>
                                <dt class="col-5">Website</dt>
                                <dd class="col-7 text-break">{{ $partnerProfile->website ?? '-' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Products</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead><tr><th>Name</th><th>Type</th><th>Category</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @forelse ($partnerProfile->products as $product)
                                            <tr><td class="fw-semibold">{{ $product->name }}</td><td>{{ ucfirst($product->item_type) }}</td><td>{{ $product->category ?? '-' }}</td><td>{{ $product->is_active ? 'Active' : 'Inactive' }}</td></tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-body-secondary py-4">No products.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Presentations</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead><tr><th>Title</th><th>Status</th><th>Plant Type</th><th class="text-center">Pages</th></tr></thead>
                                    <tbody>
                                        @forelse ($partnerProfile->presentations as $presentation)
                                            <tr><td class="fw-semibold">{{ $presentation->title }}</td><td>{{ ucfirst(str_replace('_', ' ', $presentation->status)) }}</td><td>{{ $presentation->plantType?->name ?? '-' }}</td><td class="text-center">{{ $presentation->page_count ?? '-' }}</td></tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-body-secondary py-4">No presentations.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Members</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead><tr><th>User</th><th>Role</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @forelse ($partnerProfile->members as $member)
                                            <tr><td>{{ $member->user?->email ?? $member->user_id }}</td><td>{{ ucfirst($member->member_role) }}</td><td>{{ ucfirst($member->status) }}</td></tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-body-secondary py-4">No members.</td></tr>
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
