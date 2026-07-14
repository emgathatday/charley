@extends('layouts.master')

@section('title', 'Library Items')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">Library Items</h1></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Library Items</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-journal-text"></i></span><div class="info-box-content"><span class="info-box-text">Total Items</span><span class="info-box-number">{{ $stats['total'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-check2-circle"></i></span><div class="info-box-content"><span class="info-box-text">Published</span><span class="info-box-number">{{ $stats['published'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-warning"><i class="bi bi-hourglass-split"></i></span><div class="info-box-content"><span class="info-box-text">Pending Review</span><span class="info-box-number">{{ $stats['pending_review'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-info"><i class="bi bi-cpu"></i></span><div class="info-box-content"><span class="info-box-text">AI Trainable</span><span class="info-box-number">{{ $stats['ai_trainable'] }}</span></div></div></div>
        </div>

        <div class="card card-outline card-primary mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Review Queue</h3>
                <a href="{{ route('admin.dashboard.library.items.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Create Item</a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.dashboard.library.items.index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-3"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" placeholder="Title, author, source"></div>
                    <div class="col-md-3 col-lg-2"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option>All statuses</option><option>Draft</option><option>Published</option><option>Archived</option></select></div>
                    <div class="col-md-3 col-lg-2"><label class="form-label" for="access">Access</label><select class="form-select" id="access" name="access"><option>All access levels</option><option>Professional only</option><option>Partner only</option><option>Gold plus</option></select></div>
                    <div class="col-md-3 col-lg-2"><label class="form-label" for="content_type">Content</label><select class="form-select" id="content_type" name="content_type"><option>All content</option><option>Document</option><option>Video</option><option>Case study</option></select></div>
                    <div class="col-lg-3 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button><a href="{{ route('admin.dashboard.library.items.index') }}" class="btn btn-outline-secondary">Reset</a></div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Item</th><th>Category</th><th>Plant Type</th><th>Type</th><th>Access</th><th>Review State</th><th>Controls</th><th>Counts</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td><div class="fw-semibold">{{ $item['title'] }}</div><code>{{ $item['slug'] }}</code><div class="small text-body-secondary">{{ $item['file_label'] }} <span class="badge text-bg-light ms-1">{{ $item['file_media_id'] }}</span></div></td>
                                    <td>{{ $item['category'] }}</td>
                                    <td>{{ $item['plant_type'] }}</td>
                                    <td><span class="badge text-bg-secondary">{{ Str::headline($item['content_type']) }}</span><div class="small text-body-secondary">{{ Str::headline($item['item_type']) }}</div></td>
                                    <td><span class="badge text-bg-info">{{ Str::headline($item['access_level']) }}</span></td>
                                    <td><span class="badge text-bg-{{ $item['approval_status'] === 'Approved' ? 'success' : 'warning' }}">{{ $item['approval_status'] }}</span><div class="small text-body-secondary">{{ Str::headline($item['status']) }} @if($item['approved_at']) by {{ $item['approved_by'] }} @endif</div></td>
                                    <td><span class="badge text-bg-{{ $item['download_allowed'] ? 'success' : 'secondary' }}">Download {{ $item['download_allowed'] ? 'on' : 'off' }}</span><br><span class="badge text-bg-{{ $item['copy_paste_disabled'] ? 'danger' : 'success' }}">Copy {{ $item['copy_paste_disabled'] ? 'blocked' : 'allowed' }}</span><br><span class="badge text-bg-{{ $item['is_ai_trainable'] ? 'primary' : 'secondary' }}">AI {{ $item['is_ai_trainable'] ? 'trainable' : 'excluded' }}</span></td>
                                    <td><div><i class="bi bi-eye me-1"></i>{{ number_format($item['view_count']) }}</div><div><i class="bi bi-download me-1"></i>{{ number_format($item['download_count']) }}</div></td>
                                    <td class="text-end"><div class="btn-group btn-group-sm"><a class="btn btn-outline-secondary" href="{{ route('admin.dashboard.library.items.show', $item['id']) }}"><i class="bi bi-eye"></i></a><a class="btn btn-outline-primary" href="{{ route('admin.dashboard.library.items.edit', $item['id']) }}"><i class="bi bi-pencil-square"></i></a></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div></div>
@endsection