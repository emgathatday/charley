@extends('layouts.master')

@section('title', 'Library Hotspots')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Library Hotspots</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.index') }}">Library</a></li><li class="breadcrumb-item active">Hotspots</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-journal-richtext"></i></span><div class="info-box-content"><span class="info-box-text">Items</span><span class="info-box-number">{{ $stats['items'] }}</span></div></div></div>
            <div class="col-md-4"><div class="info-box"><span class="info-box-icon text-bg-info"><i class="bi bi-bullseye"></i></span><div class="info-box-content"><span class="info-box-text">Hotspots</span><span class="info-box-number">{{ $stats['hotspots'] }}</span></div></div></div>
            <div class="col-md-4"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-diagram-3"></i></span><div class="info-box-content"><span class="info-box-text">Domains</span><span class="info-box-number">{{ $stats['domains'] }}</span></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-8"><div class="card card-outline card-primary"><div class="card-header"><h3 class="card-title mb-0">Hotspot Map</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Item</th><th>Domain</th><th>Shape</th><th>Coordinates</th><th></th></tr></thead><tbody>
                @forelse ($hotspots as $hotspot)
                    <tr><td class="fw-semibold">{{ $hotspot->libraryItem?->title ?? '-' }}<div class="small text-body-secondary">{{ $hotspot->label ?: 'No label' }}</div></td><td>{{ $hotspot->knowledgeDomain?->name ?? '-' }}</td><td>{{ Str::headline($hotspot->shape_type) }}</td><td><code>{{ Str::limit(json_encode($hotspot->coordinates), 60) }}</code></td><td class="text-end"><form method="POST" action="{{ route('admin.dashboard.library.hotspots.destroy', $hotspot) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button></form></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-body-secondary py-4">No hotspots configured.</td></tr>
                @endforelse
            </tbody></table></div></div>@if ($hotspots->hasPages())<div class="card-footer">{{ $hotspots->links() }}</div>@endif</div></div>
            <div class="col-lg-4"><div class="card card-outline card-success"><div class="card-header"><h3 class="card-title mb-0">Create Hotspot</h3></div><form method="POST" action="{{ route('admin.dashboard.library.hotspots.store') }}">@csrf<div class="card-body"><div class="mb-3"><label class="form-label" for="library_item_id">Library Item</label><select id="library_item_id" name="library_item_id" class="form-select" required>@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->title }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label" for="knowledge_domain_id">Knowledge Domain</label><select id="knowledge_domain_id" name="knowledge_domain_id" class="form-select" required>@foreach ($domains as $domain)<option value="{{ $domain->id }}">{{ $domain->name }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label" for="label">Label</label><input id="label" name="label" class="form-control"></div><div class="row g-2 mb-3"><div class="col-6"><label class="form-label" for="shape_type">Shape</label><select id="shape_type" name="shape_type" class="form-select">@foreach ($shapes as $shape)<option value="{{ $shape }}">{{ Str::headline($shape) }}</option>@endforeach</select></div><div class="col-6"><label class="form-label" for="sort_order">Order</label><input id="sort_order" name="sort_order" type="number" min="0" value="1" class="form-control"></div></div><div><label class="form-label" for="coordinates">Coordinates JSON</label><textarea id="coordinates" name="coordinates" rows="4" class="form-control" required>[[10,10],[80,10],[80,60],[10,60]]</textarea></div></div><div class="card-footer"><button class="btn btn-success" type="submit"><i class="bi bi-plus-circle me-1"></i>Create</button></div></form></div></div>
        </div>
    </div></div>
@endsection