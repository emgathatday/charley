@extends('layouts.app')

@section('title', 'Admin Operations')

@php
    $statusBadge = fn (?string $status) => match ($status) {
        'resolved', 'closed', 'approved' => 'badge-success',
        'rejected', 'urgent' => 'badge-danger',
        'pending', 'high' => 'badge-warning',
        default => 'badge-info',
    };
@endphp

@section('content_header')
    <div class="page-header">
        <div>
            <div class="page-title">Admin Operations</div>
            <div class="page-subtitle">Support, approval queues, platform settings, and admin integrations.</div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dashboard.admin-operations.support-tickets.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle" aria-hidden="true"></i>Ticket</a>
            <a href="{{ route('admin.dashboard.admin-operations.admin-integrations.create') }}" class="btn"><i class="bi bi-plug" aria-hidden="true"></i>Integration</a>
        </div>
    </div>
@endsection

@section('content')
    @include('templates.components.alert-session')

    @if (in_array(false, $availableTables, true))
        <div class="alert alert-warning">Admin operations tables are not fully available. Run migrations to populate all panels.</div>
    @endif

    <div class="stats-row" style="grid-template-columns:repeat(4,minmax(0,1fr));padding:0;margin-bottom:22px;">
        <div class="stat-card blue"><div class="stat-label">Open Tickets</div><div class="stat-value">{{ number_format($stats['open_tickets']) }}</div><div class="stat-sub">Support inbox</div></div>
        <div class="stat-card red"><div class="stat-label">Active Penalties</div><div class="stat-value">{{ number_format($stats['active_penalties']) }}</div><div class="stat-sub">Warnings and freezes</div></div>
        <div class="stat-card amber"><div class="stat-label">Pending Approvals</div><div class="stat-value">{{ number_format($stats['pending_approvals']) }}</div><div class="stat-sub">Content queue</div></div>
        <div class="stat-card emerald"><div class="stat-label">Integrations</div><div class="stat-value">{{ number_format($stats['integrations']) }}</div><div class="stat-sub">Connected admin accounts</div></div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <form method="GET" action="{{ route('admin.dashboard.admin-operations.index') }}" class="d-flex align-items-center gap-2 flex-wrap"><div><div class="table-title">Support tickets</div><div class="table-meta">Latest member support requests</div></div><select class="filter-select" name="ticket_status" onchange="this.form.submit()"><option value="">All statuses</option>@foreach (['open', 'pending', 'resolved', 'closed'] as $status)<option value="{{ $status }}" @selected(($filters['ticket_status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></form>
            <a href="{{ route('admin.dashboard.admin-operations.support-tickets.create') }}" class="btn"><i class="bi bi-plus-circle" aria-hidden="true"></i>Create</a>
        </div>
        <div class="table-responsive"><table class="qa-table"><thead><tr><th>Subject</th><th>User</th><th>Category</th><th>Priority</th><th>Status</th><th>Assigned</th><th class="text-end">Actions</th></tr></thead><tbody>
            @forelse ($supportTickets as $ticket)
                <tr><td class="fw-semibold">{{ $ticket->subject }}</td><td>{{ $ticket->user?->email ?? 'User #'.$ticket->user_id }}</td><td>{{ str_replace('_', ' ', $ticket->category) }}</td><td><span class="badge {{ $statusBadge($ticket->priority) }}">{{ $ticket->priority }}</span></td><td><span class="badge {{ $statusBadge($ticket->status) }}">{{ $ticket->status }}</span></td><td>{{ $ticket->assignee?->email ?? 'Unassigned' }}</td><td class="text-end"><form method="POST" action="{{ route('admin.dashboard.admin-operations.support-tickets.resolve', $ticket) }}">@csrf<input type="hidden" name="content" value="Resolved from dashboard"><button class="btn" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Resolve</button></form></td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary py-4">No tickets found.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>

    <div class="detail-grid" style="margin-top:20px;">
        <section class="table-card">
            <div class="table-header"><div><div class="table-title">Account Penalty & Freeze Audit</div><div class="table-meta">Latest warnings, suspensions, and account freezes from IAM security actions</div></div></div>
            <div class="table-responsive"><table class="qa-table"><thead><tr><th>User</th><th>Action</th><th>Reason</th><th>Starts</th><th>Ends</th></tr></thead><tbody>@forelse ($accountPenalties as $penalty)<tr><td>{{ $penalty->user?->email ?? 'User #'.$penalty->user_id }}</td><td><span class="badge badge-danger">{{ str_replace('_', ' ', $penalty->action_type) }}</span></td><td>{{ Str::limit($penalty->reason, 80) }}</td><td>{{ optional($penalty->starts_at)->format('Y-m-d') }}</td><td>{{ optional($penalty->ends_at)->format('Y-m-d') ?? 'Open' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">No penalties found.</td></tr>@endforelse</tbody></table></div>
        </section>

        <section class="table-card">
            <div class="table-header"><div><div class="table-title">Content approval queue</div><div class="table-meta">Pending governance decisions</div></div></div>
            <div class="table-responsive"><table class="qa-table"><thead><tr><th>Title</th><th>Type</th><th>Priority</th><th>Status</th><th>Assigned</th><th class="text-end">Actions</th></tr></thead><tbody>@forelse ($contentApprovals as $item)<tr><td>{{ $item->content_title }}</td><td>{{ $item->content_type_label }}</td><td><span class="badge {{ $statusBadge($item->priority) }}">{{ $item->priority }}</span></td><td><span class="badge {{ $statusBadge($item->status) }}">{{ $item->status }}</span></td><td>{{ $item->assignee?->email ?? 'Unassigned' }}</td><td class="text-end"><div class="d-flex justify-content-end gap-2"><form method="POST" action="{{ route('admin.dashboard.admin-operations.content-approvals.approve', $item) }}">@csrf<button class="btn" type="submit"><i class="bi bi-check2" aria-hidden="true"></i></button></form><form method="POST" action="{{ route('admin.dashboard.admin-operations.content-approvals.reject', $item) }}">@csrf<button class="btn" type="submit"><i class="bi bi-x-lg" aria-hidden="true"></i></button></form></div></td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-4">No content approvals found.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>

    <div class="detail-grid" style="margin-top:20px;">
        <section class="table-card"><div class="table-header"><div><div class="table-title">Platform settings</div><div class="table-meta">Global rules and limits</div></div><a href="{{ route('admin.dashboard.admin-operations.platform-settings.edit') }}" class="btn"><i class="bi bi-gear" aria-hidden="true"></i>Edit</a></div><div class="table-responsive"><table class="qa-table"><thead><tr><th>Key</th><th>Value</th><th>Group</th><th>Description</th></tr></thead><tbody>@forelse ($platformSettings as $setting)<tr><td><code>{{ $setting->key }}</code></td><td>{{ $setting->value }}</td><td>{{ $setting->group }}</td><td>{{ $setting->description }}</td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-4">No settings found.</td></tr>@endforelse</tbody></table></div></section>
        <section class="table-card"><div class="table-header"><div><div class="table-title">Admin Integrations</div><div class="table-meta">Email and workflow connections</div></div><a href="{{ route('admin.dashboard.admin-operations.admin-integrations.create') }}" class="btn"><i class="bi bi-plug" aria-hidden="true"></i>Connect</a></div><div class="table-responsive"><table class="qa-table"><thead><tr><th>Admin</th><th>Provider</th><th>Expires</th></tr></thead><tbody>@forelse ($adminIntegrations as $integration)<tr><td>{{ $integration->user?->email ?? 'User #'.$integration->user_id }}</td><td><span class="badge badge-success">{{ $integration->provider }}</span></td><td>{{ optional($integration->token_expires_at)->format('Y-m-d H:i') }}</td></tr>@empty<tr><td colspan="3" class="text-center text-secondary py-4">No integrations found.</td></tr>@endforelse</tbody></table></div></section>
    </div>
@endsection
