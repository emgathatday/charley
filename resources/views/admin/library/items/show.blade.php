@extends('layouts.master')

@section('title', 'Library Item Detail')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Library Item Detail</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.items.index') }}">Library Items</a></li><li class="breadcrumb-item active" aria-current="page">Detail</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <h2 class="h4 mb-1">{{ $item['title'] }}</h2>
                <div class="text-body-secondary"><code>{{ $item['slug'] }}</code></div>
            </div>
            <div class="d-flex gap-2"><a href="{{ route('admin.dashboard.library.items.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a><a href="{{ route('admin.dashboard.library.items.edit', $item['id']) }}" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-eye"></i></span><div class="info-box-content"><span class="info-box-text">Views</span><span class="info-box-number">{{ number_format($item['view_count']) }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-download"></i></span><div class="info-box-content"><span class="info-box-text">Downloads</span><span class="info-box-number">{{ number_format($item['download_count']) }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-{{ $item['download_allowed'] ? 'success' : 'secondary' }}"><i class="bi bi-cloud-arrow-down"></i></span><div class="info-box-content"><span class="info-box-text">Download</span><span class="info-box-number">{{ $item['download_allowed'] ? 'Allowed' : 'Blocked' }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-{{ $item['is_ai_trainable'] ? 'info' : 'secondary' }}"><i class="bi bi-cpu"></i></span><div class="info-box-content"><span class="info-box-text">AI Training</span><span class="info-box-number">{{ $item['is_ai_trainable'] ? 'Enabled' : 'Excluded' }}</span></div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card card-outline card-primary mb-3">
                    <div class="card-header"><h3 class="card-title mb-0">Content Review</h3></div>
                    <div class="card-body">
                        <p class="lead mb-3">{{ $item['summary'] }}</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4"><div class="text-body-secondary small">Category</div><div class="fw-semibold">{{ $item['category'] }}</div></div>
                            <div class="col-md-4"><div class="text-body-secondary small">Plant Type</div><div class="fw-semibold">{{ $item['plant_type'] }}</div></div>
                            <div class="col-md-4"><div class="text-body-secondary small">Content Type</div><div class="fw-semibold">{{ Str::headline($item['content_type']) }}</div></div>
                            <div class="col-md-4"><div class="text-body-secondary small">Item Type</div><div class="fw-semibold">{{ Str::headline($item['item_type']) }}</div></div>
                            <div class="col-md-4"><div class="text-body-secondary small">Author</div><div class="fw-semibold">{{ $item['author'] }}</div></div>
                            <div class="col-md-4"><div class="text-body-secondary small">Source</div><div class="fw-semibold">{{ $item['source'] }}</div></div>
                        </div>
                        <div class="border rounded p-3 bg-body-tertiary">{{ $item['content'] }}</div>
                    </div>
                </div>

                <div class="card card-outline card-secondary">
                    <div class="card-header"><h3 class="card-title mb-0">Recent Access Logs</h3></div>
                    <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>User</th><th>Action</th><th>IP Address</th><th>Time</th></tr></thead><tbody>@foreach($recentAccessLogs as $log)<tr><td>{{ $log['user'] }}</td><td><span class="badge text-bg-{{ $log['action'] === 'download' ? 'success' : 'info' }}">{{ Str::headline($log['action']) }}</span></td><td><code>{{ $log['ip_address'] }}</code></td><td>{{ $log['created_at'] }}</td></tr>@endforeach</tbody></table></div></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-outline card-info mb-3">
                    <div class="card-header"><h3 class="card-title mb-0">Visibility and Approval</h3></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5">Approval</dt><dd class="col-7"><span class="badge text-bg-{{ $item['approval_status'] === 'Approved' ? 'success' : 'warning' }}">{{ $item['approval_status'] }}</span></dd>
                            <dt class="col-5">Status</dt><dd class="col-7">{{ Str::headline($item['status']) }}</dd>
                            <dt class="col-5">Access</dt><dd class="col-7">{{ Str::headline($item['access_level']) }}</dd>
                            <dt class="col-5">Approved by</dt><dd class="col-7">{{ $item['approved_by'] ?? 'Not assigned' }}</dd>
                            <dt class="col-5">Approved at</dt><dd class="col-7">{{ $item['approved_at'] ?? 'Pending' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card card-outline card-warning">
                    <div class="card-header"><h3 class="card-title mb-0">Media and Restrictions</h3></div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3"><span class="btn btn-outline-secondary disabled"><i class="bi bi-file-earmark-richtext"></i></span><div><div class="fw-semibold">{{ $item['file_label'] }}</div><code>{{ $item['file_media_id'] }}</code></div></div>
                        <div class="d-flex flex-wrap gap-2"><span class="badge text-bg-{{ $item['download_allowed'] ? 'success' : 'secondary' }}">Download {{ $item['download_allowed'] ? 'allowed' : 'blocked' }}</span><span class="badge text-bg-{{ $item['copy_paste_disabled'] ? 'danger' : 'success' }}">Copy {{ $item['copy_paste_disabled'] ? 'disabled' : 'allowed' }}</span><span class="badge text-bg-{{ $item['is_ai_trainable'] ? 'primary' : 'secondary' }}">AI {{ $item['is_ai_trainable'] ? 'trainable' : 'excluded' }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div></div>
@endsection