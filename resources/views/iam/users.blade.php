@extends('layouts.master')

@section('title', $pageTitle)

@php
    $statCards = [
        ['label' => 'Total Users', 'value' => $stats['total_users'], 'color' => 'primary', 'icon' => 'bi-people'],
        ['label' => 'Active', 'value' => $stats['active_members'], 'color' => 'success', 'icon' => 'bi-person-check'],
        ['label' => 'Pending Approval', 'value' => $stats['pending_approvals'], 'color' => 'warning', 'icon' => 'bi-hourglass-split'],
        ['label' => 'Frozen & Suspended', 'value' => $stats['frozen_users'] + $stats['suspended_users'], 'color' => 'danger', 'icon' => 'bi-lock'],
    ];
    $showsMemberProfileColumns = $memberView !== 'administrators';
@endphp

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">{{ $pageTitle }}</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="#">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">IAM</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                @foreach ($statCards as $stat)
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon text-bg-{{ $stat['color'] }}"><i class="bi {{ $stat['icon'] }}"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ $stat['label'] }}</span>
                                <span class="info-box-number">{{ number_format($stat['value']) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">User List</h3>
                        <ul class="nav nav-pills card-header-pills">
                            @foreach ($tabs as $tabKey => $tab)
                                <li class="nav-item">
                                    <a class="nav-link {{ $filters['tab'] === $tabKey ? 'active' : '' }}" href="{{ route('admin.dashboard.iam.users', array_merge(request()->except('page'), ['tab' => $tabKey])) }}">
                                        {{ $tab['label'] }}
                                        <span class="badge {{ $filters['tab'] === $tabKey ? 'text-bg-light text-primary' : 'text-bg-secondary' }} ms-1">
                                            {{ number_format($tab['count']) }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <span class="badge text-bg-secondary">{{ number_format($users->total()) }} results</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.dashboard.iam.users') }}">
                        <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                        <input type="hidden" name="member_view" value="{{ $memberView }}">
                        <div class="row g-3 align-items-end">
                            <div class="{{ $showsMemberProfileColumns ? 'col-lg-6' : 'col-lg-10' }}">
                                <label class="form-label" for="keyword">Name or Email</label>
                                <input type="text" class="form-control" id="keyword" name="keyword" value="{{ $filters['keyword'] }}" placeholder="Enter name or email">
                            </div>
                            @if ($showsMemberProfileColumns)
                                <div class="col-lg-4">
                                    <label class="form-label" for="plant_type_id">Plant Type</label>
                                    <select class="form-select" id="plant_type_id" name="plant_type_id">
                                        <option value="">All plant types</option>
                                        @foreach ($plantTypeOptions as $plantTypeId => $plantTypeName)
                                            <option value="{{ $plantTypeId }}" @selected((string) $filters['plant_type_id'] === (string) $plantTypeId)>{{ $plantTypeName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-lg-2">
                                <button class="btn btn-primary w-100" type="submit">
                                    <i class="bi bi-funnel me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0 border-top">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    @if ($showsMemberProfileColumns)
                                        <th>Plant Type</th>
                                        <th>Experience</th>
                                    @else
                                        <th>Security</th>
                                    @endif
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="fw-semibold text-nowrap">{{ $user->display_id }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $user->display_name }}</span>
                                            <div class="small text-secondary">{{ $user->latest_verification_status }}</div>
                                        </td>
                                        <td><a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a></td>
                                        <td><span class="badge text-bg-info">{{ str_replace('_', ' ', $user->role) }}</span></td>
                                        @if ($showsMemberProfileColumns)
                                            <td>{{ $user->plant_type_label }}</td>
                                            <td class="text-nowrap">{{ $user->experience_label }}</td>
                                        @else
                                            <td>{{ $user->security_label }}</td>
                                        @endif
                                        <td><span class="badge {{ $user->status_badge }}">{{ $user->status_label }}</span></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a
                                                    class="btn btn-outline-secondary"
                                                    href="{{ route('admin.dashboard.iam.users.show', $user) }}"
                                                    title="View details"
                                                >
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                </a>
                                                <button
                                                    class="btn btn-outline-danger js-user-freeze"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#userFreezeModal"
                                                    data-user-id="{{ $user->display_id }}"
                                                    data-user-name="{{ $user->display_name }}"
                                                    data-user-email="{{ $user->email }}"
                                                    title="Freeze or suspend"
                                                >
                                                    <i class="bi bi-lock" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showsMemberProfileColumns ? 8 : 7 }}" class="text-center text-secondary py-4">No users match the selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="text-secondary small">
                        Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }} users
                    </span>
                    {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userDetailModal" tabindex="-1" aria-labelledby="userDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">ID</dt>
                        <dd class="col-sm-8" data-user-detail="id"></dd>
                        <dt class="col-sm-4">Full Name</dt>
                        <dd class="col-sm-8" data-user-detail="name"></dd>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8" data-user-detail="email"></dd>
                        <dt class="col-sm-4">Role</dt>
                        <dd class="col-sm-8" data-user-detail="role"></dd>
                        @if ($showsMemberProfileColumns)
                            <dt class="col-sm-4">Plant Type</dt>
                            <dd class="col-sm-8" data-user-detail="plantType"></dd>
                        @endif
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8" data-user-detail="status"></dd>
                        <dt class="col-sm-4">Verification</dt>
                        <dd class="col-sm-8" data-user-detail="verification"></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userFreezeModal" tabindex="-1" aria-labelledby="userFreezeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userFreezeModalLabel">Freeze or Suspend User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Review this IAM account before applying a freeze or suspension workflow.</p>
                    <div class="border rounded p-3 bg-body-tertiary">
                        <div class="fw-semibold" data-user-freeze="name"></div>
                        <div class="text-secondary small" data-user-freeze="email"></div>
                        <div class="text-secondary small" data-user-freeze="id"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Confirm demo action</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.js-user-detail').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById('userDetailModal')
                ;['id', 'name', 'email', 'role', @if ($showsMemberProfileColumns) 'plantType', @endif 'status', 'verification'].forEach((field) => {
                    modal.querySelector(`[data-user-detail="${field}"]`).textContent = button.dataset[`user${field.charAt(0).toUpperCase()}${field.slice(1)}`]
                })
            })
        })

        document.querySelectorAll('.js-user-freeze').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById('userFreezeModal')
                ;['id', 'name', 'email'].forEach((field) => {
                    modal.querySelector(`[data-user-freeze="${field}"]`).textContent = button.dataset[`user${field.charAt(0).toUpperCase()}${field.slice(1)}`]
                })
            })
        })
    </script>
@endpush


