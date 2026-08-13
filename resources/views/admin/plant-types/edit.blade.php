@extends('layouts.rebuild-dashboard')

@section('title', 'View/Edit Plant Type')

@section('content')
    @php
        $usageCounts = $usageCounts ?? [];
        $countFor = fn (string $key) => $usageCounts[$key] ?? ($plantType->{$key . '_count'} ?? 0);
        $isActive = (bool) old('is_active', (int) $plantType->is_active);
    @endphp

    @include('templates.components.alert-session')

    <a href="{{ route('admin.dashboard.plant-types.index') }}" class="back-link">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
        Back to Plant Types
    </a>

    <form id="plant-type-form" method="POST" action="{{ route('admin.dashboard.plant-types.update', $plantType) }}">
        @csrf
        @method('PUT')

        <div class="page-head plant-type-hero">
            <div class="page-head-left">
                <div @class(['company-logo', $plantType->is_active ? 'plant-type-tone-amber' : 'plant-type-tone-muted'])>
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-library-and-pfd-content-path"></use></svg>
                </div>
                <div>
                    <div class="page-title-row">
                        <div class="page-title">{{ old('name', $plantType->name) }}</div>
                        <span @class(['badge', $isActive ? 'plant-type-badge-active' : 'plant-type-badge-muted'])>{{ $isActive ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="page-sub">
                        <span>Plant Type #{{ $plantType->id }}</span>
                        <span class="sep"></span>
                        <span>Slug: {{ old('slug', $plantType->slug) }}</span>
                        <span class="sep"></span>
                        <span>Sort order: {{ old('sort_order', $plantType->sort_order) }}</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" type="button">Set inactive</button>
                <button class="btn btn-primary" type="submit">Save edits</button>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 plant-type-stat-row">
            <div class="col"><div class="stat-card"><div class="stat-label">Library Items</div><div class="stat-value">{{ $countFor('library_items') }}</div><div class="stat-sub">Library links</div></div></div>
            <div class="col"><div class="stat-card"><div class="stat-label">Q&amp;A</div><div class="stat-value">{{ $countFor('questions') }}</div><div class="stat-sub">Question links</div></div></div>
            <div class="col"><div class="stat-card"><div class="stat-label">Partner Profiles</div><div class="stat-value">{{ $countFor('partner_profiles') }}</div><div class="stat-sub">Partner links</div></div></div>
            <div class="col"><div class="stat-card"><div class="stat-label">Service Requests</div><div class="stat-value">{{ $countFor('service_requests') }}</div><div class="stat-sub">Service links</div></div></div>
        </div>

        <div class="detail-grid plant-type-detail-grid">
            <div class="col-main">
                @include('admin.plant-types._form', ['plantType' => $plantType])

                <div class="card card-padded">
                    <div class="verification-detail-head">
                        <div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-platform-settings-ai-assistant"></use></svg>Related usage</div>
                        <span class="card-title-count">FK tables</span>
                    </div>
                    <div class="table-scroll">
                        <table class="plant-type-relation-table">
                            <thead>
                                <tr><th>Module</th><th>Table</th><th>Records</th><th>Operational note</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Library</td><td>library_items</td><td><strong>{{ $countFor('library_items') }}</strong></td><td>Content filtering and PFD library navigation.</td></tr>
                                <tr><td>Q&amp;A</td><td>questions</td><td><strong>{{ $countFor('questions') }}</strong></td><td>Question bank scoped by process catalog.</td></tr>
                                <tr><td>Partners</td><td>partner_profiles</td><td><strong>{{ $countFor('partner_profiles') }}</strong></td><td>Partner expertise and discovery filters.</td></tr>
                                <tr><td>Services</td><td>service_requests</td><td><strong>{{ $countFor('service_requests') }}</strong></td><td>Requests retain historical plant type context.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-side">
                <div class="side-card">
                    <div class="card card-padded decision-panel">
                        <div class="card-title">Admin actions</div>
                        <div class="decision-actions">
                            <button class="btn btn-primary btn-block-spaced" type="submit">Save changes</button>
                            <button class="btn btn-outline btn-block-spaced" type="button">Duplicate record</button>
                            <button class="btn btn-ghost btn-block-spaced ann-delete-btn" type="button">Set inactive</button>
                        </div>
                        <div class="plant-type-warning-note">Inactive plant types stay attached to existing records, but should be hidden from new selection flows.</div>
                    </div>
                    <div class="card card-padded">
                        <div class="card-title">Schema fields</div>
                        <div class="plant-type-schema-list">
                            <span>ID</span><span>Name</span><span>Slug</span><span>Description</span><span>Status</span><span>Sort order</span><span>Created at</span><span>Updated at</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
