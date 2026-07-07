@extends('layouts.master')

@section('title', 'Library Admin')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Library Admin</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item active" aria-current="page">Library</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-journal-richtext"></i></span><div class="info-box-content"><span class="info-box-text">Items</span><span class="info-box-number">{{ $stats['items'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-warning"><i class="bi bi-hourglass-split"></i></span><div class="info-box-content"><span class="info-box-text">Pending</span><span class="info-box-number">{{ $stats['pending'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-check2-circle"></i></span><div class="info-box-content"><span class="info-box-text">Published</span><span class="info-box-number">{{ $stats['published'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-info"><i class="bi bi-download"></i></span><div class="info-box-content"><span class="info-box-text">Downloads</span><span class="info-box-number">{{ $stats['downloads'] }}</span></div></div></div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-8"><div class="card card-outline card-warning"><div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Approval Queue</h3><a href="{{ route('admin.dashboard.library.approvals') }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-list-check me-1"></i>Review</a></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Title</th><th>Category</th><th>Access</th><th>Updated</th></tr></thead><tbody>@forelse ($pendingItems as $item)<tr><td class="fw-semibold">{{ $item->title }}</td><td>{{ $item->category?->title ?? '-' }}</td><td><span class="badge text-bg-light">{{ Str::headline($item->access_level) }}</span></td><td>{{ optional($item->updated_at)->format('Y-m-d H:i') }}</td></tr>@empty<tr><td colspan="4" class="text-center text-body-secondary py-4">No pending library items.</td></tr>@endforelse</tbody></table></div></div></div></div>
            <div class="col-lg-4"><div class="card card-outline card-info"><div class="card-header"><h3 class="card-title mb-0">Access Rules</h3></div><div class="card-body p-0"><table class="table table-sm mb-0"><tbody>@forelse ($rules as $rule)<tr><td class="fw-semibold">{{ Str::headline($rule->partner_tier) }}</td><td>@if ($rule->can_download)<span class="badge text-bg-success">Download</span>@else<span class="badge text-bg-secondary">View Only</span>@endif</td></tr>@empty<tr><td class="text-body-secondary py-3">No access rules configured.</td></tr>@endforelse</tbody></table></div></div></div>
        </div>
        <div class="card card-outline card-primary"><div class="card-header"><h3 class="card-title mb-0">Recent Access Logs</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Item</th><th>User</th><th>Action</th><th>IP</th><th>Time</th></tr></thead><tbody>@forelse ($recentLogs as $log)<tr><td>{{ $log->item?->title ?? '-' }}</td><td>{{ $log->user?->email ?? '-' }}</td><td><span class="badge text-bg-{{ $log->action === 'download' ? 'primary' : 'secondary' }}">{{ Str::headline($log->action) }}</span></td><td><code>{{ $log->ip_address }}</code></td><td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-body-secondary py-4">No access activity recorded.</td></tr>@endforelse</tbody></table></div></div></div>
    </div></div>
@endsection
