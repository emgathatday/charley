@extends('layouts.master')

@section('title', 'Admin Operations')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">Admin Operations</h1></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Operations</li>
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
                    <span>Admin operations tables are not fully available. Run migrations to populate all panels.</span>
                </div>
            @endif

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <p class="text-body-secondary mb-0">Review support, account controls, content approvals, platform settings, and admin email integrations.</p>
                <div class="btn-group">
                    <a href="{{ route('admin.dashboard.admin-operations.support-tickets.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Ticket</a>
                    <a href="{{ route('admin.dashboard.admin-operations.account-penalties.create') }}" class="btn btn-outline-primary"><i class="bi bi-shield-plus me-1"></i>Penalty</a>
                    <a href="{{ route('admin.dashboard.admin-operations.admin-integrations.create') }}" class="btn btn-outline-primary"><i class="bi bi-plug me-1"></i>Integration</a>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-lg-3 col-sm-6"><div class="info-box shadow-sm mb-0"><span class="info-box-icon text-bg-info"><i class="bi bi-life-preserver"></i></span><div class="info-box-content"><span class="info-box-text">Open tickets</span><span class="info-box-number">{{ $stats['open_tickets'] }}</span></div></div></div>
                <div class="col-lg-3 col-sm-6"><div class="info-box shadow-sm mb-0"><span class="info-box-icon text-bg-danger"><i class="bi bi-shield-exclamation"></i></span><div class="info-box-content"><span class="info-box-text">Active penalties</span><span class="info-box-number">{{ $stats['active_penalties'] }}</span></div></div></div>
                <div class="col-lg-3 col-sm-6"><div class="info-box shadow-sm mb-0"><span class="info-box-icon text-bg-warning"><i class="bi bi-inboxes"></i></span><div class="info-box-content"><span class="info-box-text">Pending approvals</span><span class="info-box-number">{{ $stats['pending_approvals'] }}</span></div></div></div>
                <div class="col-lg-3 col-sm-6"><div class="info-box shadow-sm mb-0"><span class="info-box-icon text-bg-success"><i class="bi bi-envelope-check"></i></span><div class="info-box-content"><span class="info-box-text">Integrations</span><span class="info-box-number">{{ $stats['integrations'] }}</span></div></div></div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <form method="GET" action="{{ route('admin.dashboard.admin-operations.index') }}" class="d-flex align-items-center gap-2"><h3 class="card-title mb-0 me-2">Support tickets</h3><select class="form-select form-select-sm" name="ticket_status" onchange="this.form.submit()"><option value="">All statuses</option>@foreach (['open', 'pending', 'resolved', 'closed'] as $status)<option value="{{ $status }}" @selected(($filters['ticket_status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></form>
                    <a href="{{ route('admin.dashboard.admin-operations.support-tickets.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-circle me-1"></i>Create</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Subject</th><th>User</th><th>Category</th><th>Priority</th><th>Status</th><th>Assigned</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                @forelse ($supportTickets as $ticket)
                                    <tr><td class="fw-semibold">{{ $ticket->subject }}</td><td>{{ $ticket->user?->email ?? 'User #'.$ticket->user_id }}</td><td>{{ str_replace('_', ' ', $ticket->category) }}</td><td><span class="badge text-bg-light">{{ $ticket->priority }}</span></td><td><span class="badge text-bg-info">{{ $ticket->status }}</span></td><td>{{ $ticket->assignee?->email ?? 'Unassigned' }}</td><td class="text-end"><form method="POST" action="{{ route('admin.dashboard.admin-operations.support-tickets.resolve', $ticket) }}" class="d-inline">@csrf<input type="hidden" name="content" value="Resolved from dashboard"><button class="btn btn-sm btn-success" type="submit"><i class="bi bi-check2"></i></button></form></td></tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-body-secondary py-4">No tickets found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-xl-6">
                    <div class="card card-outline card-danger h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Account penalties</h3>
                            <a href="{{ route('admin.dashboard.admin-operations.account-penalties.create') }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-shield-plus me-1"></i>Create</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>User</th><th>Action</th><th>Reason</th><th>Starts</th><th>Ends</th></tr></thead>
                                    <tbody>
                                        @forelse ($accountPenalties as $penalty)
                                            <tr><td>{{ $penalty->user?->email ?? 'User #'.$penalty->user_id }}</td><td><span class="badge text-bg-danger">{{ str_replace('_', ' ', $penalty->action_type) }}</span></td><td>{{ $penalty->reason }}</td><td>{{ optional($penalty->starts_at)->format('Y-m-d') }}</td><td>{{ optional($penalty->ends_at)->format('Y-m-d') ?? 'Open' }}</td></tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-body-secondary py-4">No penalties found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card card-outline card-warning h-100">
                        <div class="card-header"><h3 class="card-title mb-0">Content approval queue</h3></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>Title</th><th>Type</th><th>Priority</th><th>Status</th><th>Assigned</th><th class="text-end">Actions</th></tr></thead>
                                    <tbody>
                                        @forelse ($contentApprovals as $item)
                                            <tr><td class="fw-semibold">{{ $item->content_title }}</td><td>{{ $item->content_type_label }}</td><td><span class="badge text-bg-light">{{ $item->priority }}</span></td><td><span class="badge text-bg-warning">{{ $item->status }}</span></td><td>{{ $item->assignee?->email ?? 'Unassigned' }}</td><td class="text-end"><form method="POST" action="{{ route('admin.dashboard.admin-operations.content-approvals.approve', $item) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success" type="submit"><i class="bi bi-check2"></i></button></form><form method="POST" action="{{ route('admin.dashboard.admin-operations.content-approvals.reject', $item) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i></button></form></td></tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-body-secondary py-4">No content approvals found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-xl-7">
                    <div class="card card-outline card-secondary h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Platform settings</h3>
                            <a href="{{ route('admin.dashboard.admin-operations.platform-settings.edit') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear me-1"></i>Edit static form</a>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Key</th><th>Value</th><th>Group</th><th>Description</th></tr></thead>
                                <tbody>
                                    @forelse ($platformSettings as $setting)
                                        <tr><td><code>{{ $setting->key }}</code></td><td>{{ $setting->value }}</td><td>{{ $setting->group }}</td><td>{{ $setting->description }}</td></tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-body-secondary py-4">No settings found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card card-outline card-success h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Admin integrations</h3>
                            <a href="{{ route('admin.dashboard.admin-operations.admin-integrations.create') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-plug me-1"></i>Connect</a>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Admin</th><th>Provider</th><th>Expires</th></tr></thead>
                                <tbody>
                                    @forelse ($adminIntegrations as $integration)
                                        <tr><td>{{ $integration->user?->email ?? 'User #'.$integration->user_id }}</td><td><span class="badge text-bg-success">{{ $integration->provider }}</span></td><td>{{ optional($integration->token_expires_at)->format('Y-m-d H:i') }}</td></tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-body-secondary py-4">No integrations found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

