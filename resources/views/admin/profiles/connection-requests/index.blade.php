@extends('layouts.app')

@section('title', 'Connection Request Review')

@php
    $statusBadge = fn (string $status) => match ($status) {
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'cancelled' => 'badge-warning',
        default => 'badge-info',
    };

    $displayUser = fn ($user) => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')) ?: ($user?->username ?? $user?->email ?? 'Unknown user');
@endphp

@section('content_header')
    <div class="page-header" data-source-page="profiles-connection-requests-binding">
        <div>
            <div class="page-title">Connection Request Review</div>
            <div class="page-subtitle">Review Partner requests, record admin notes, and create pending connections on approval.</div>
        </div>
    </div>
@endsection

@section('content')
    @include('templates.components.alert-session')

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="stats-row" style="grid-template-columns:repeat(4,minmax(0,1fr));padding:0;margin-bottom:22px;">
        <div class="stat-card blue"><div class="stat-label">Requests</div><div class="stat-value">{{ number_format($stats['total']) }}</div></div>
        <div class="stat-card amber"><div class="stat-label">Pending</div><div class="stat-value">{{ number_format($stats['pending']) }}</div></div>
        <div class="stat-card emerald"><div class="stat-label">Approved</div><div class="stat-value">{{ number_format($stats['approved']) }}</div></div>
        <div class="stat-card indigo"><div class="stat-label">Closed</div><div class="stat-value">{{ number_format($stats['closed']) }}</div></div>
    </div>

    <form class="filter-bar" method="GET" action="{{ route('admin.dashboard.profiles.connection-requests.index') }}">
        <select id="status" class="filter-select" name="status">
            <option value="">All statuses</option>
            @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Filter</button>
        <a href="{{ route('admin.dashboard.profiles.connection-requests.index') }}" class="btn" aria-label="Reset filters"><i class="bi bi-x-lg" aria-hidden="true"></i>Reset</a>
    </form>

    <div class="table-card">
        <div class="table-header">
            <div>
                <div class="table-title">Admin Review Queue</div>
                <div class="table-meta">{{ number_format($connectionRequests->total()) }} connection request records</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="qa-table">
                <thead>
                    <tr>
                        <th>Requester Partner</th>
                        <th>Target Engineer</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Admin Note</th>
                        <th>Reviewed By</th>
                        <th>Reviewed At</th>
                        <th>Connection</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($connectionRequests as $connectionRequest)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $displayUser($connectionRequest->requester) }}</div>
                                <div class="small text-secondary">{{ $connectionRequest->requester?->email ?? 'No email' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $displayUser($connectionRequest->targetUser) }}</div>
                                <div class="small text-secondary">{{ ucfirst(str_replace('_', ' ', $connectionRequest->targetUser?->role ?? 'engineer')) }}</div>
                            </td>
                            <td>{{ $connectionRequest->reason ?? 'No reason supplied' }}</td>
                            <td><span class="badge {{ $statusBadge($connectionRequest->status) }}">{{ ucfirst($connectionRequest->status) }}</span></td>
                            <td>{{ $connectionRequest->admin_note ?? 'No admin note yet' }}</td>
                            <td>{{ $connectionRequest->reviewer ? $displayUser($connectionRequest->reviewer) : 'Not reviewed' }}</td>
                            <td>{{ $connectionRequest->reviewed_at?->format('Y-m-d H:i') ?? 'Not reviewed' }}</td>
                            <td>
                                @if ($connectionRequest->connection_id)
                                    <span class="badge badge-success">#{{ $connectionRequest->connection_id }}</span>
                                @else
                                    <span class="text-secondary">None</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($connectionRequest->status === 'pending')
                                    <div class="d-flex justify-content-end gap-2">
                                        <form method="POST" action="{{ route('admin.dashboard.profiles.connection-requests.approve', $connectionRequest) }}" class="d-flex gap-2">
                                            @csrf
                                            <input type="text" name="admin_note" class="form-control form-control-sm" placeholder="Admin note" value="{{ old('admin_note') }}">
                                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle" aria-hidden="true"></i>Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.dashboard.profiles.connection-requests.reject', $connectionRequest) }}" class="d-flex gap-2">
                                            @csrf
                                            <input type="text" name="admin_note" class="form-control form-control-sm" placeholder="Reject note" value="{{ old('admin_note') }}">
                                            <button type="submit" class="btn"><i class="bi bi-x-circle" aria-hidden="true"></i>Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-secondary">Reviewed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-secondary py-4">No connection requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-foot">
            <span class="table-foot-info">Showing {{ $connectionRequests->firstItem() ?? 0 }}-{{ $connectionRequests->lastItem() ?? 0 }} of {{ number_format($connectionRequests->total()) }}</span>
            {{ $connectionRequests->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
