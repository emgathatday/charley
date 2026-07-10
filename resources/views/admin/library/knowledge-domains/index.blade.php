@extends('layouts.master')

@section('title', 'Knowledge Domains')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Knowledge Domains</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.index') }}">Library</a></li><li class="breadcrumb-item active">Knowledge Domains</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-diagram-3"></i></span><div class="info-box-content"><span class="info-box-text">Domains</span><span class="info-box-number">{{ $stats['domains'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-check2-circle"></i></span><div class="info-box-content"><span class="info-box-text">Active</span><span class="info-box-number">{{ $stats['active'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-info"><i class="bi bi-award"></i></span><div class="info-box-content"><span class="info-box-text">Rank Tiers</span><span class="info-box-number">{{ $stats['tiers'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-warning"><i class="bi bi-people"></i></span><div class="info-box-content"><span class="info-box-text">Ranked Users</span><span class="info-box-number">{{ $stats['ranked_users'] }}</span></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-8">
                @forelse ($domains as $domain)
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">{{ $domain->name }}</h3><span class="badge text-bg-{{ $domain->status === 'active' ? 'success' : 'secondary' }}">{{ Str::headline($domain->status) }}</span></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.update', $domain) }}" class="row g-2 mb-3">@csrf @method('PUT')
                                <div class="col-md-4"><input name="name" value="{{ $domain->name }}" class="form-control" required></div>
                                <div class="col-md-3"><input name="slug" value="{{ $domain->slug }}" class="form-control" required></div>
                                <div class="col-md-3"><select name="status" class="form-select">@foreach ($statuses as $status)<option value="{{ $status }}" @selected($domain->status === $status)>{{ Str::headline($status) }}</option>@endforeach</select></div>
                                <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-save me-1"></i>Save</button></div>
                                <div class="col-12"><textarea name="description" rows="2" class="form-control">{{ $domain->description }}</textarea></div>
                            </form>
                            <div class="d-flex flex-wrap gap-2 mb-3"><span class="badge text-bg-light">{{ $domain->quizzes_count }} quizzes</span><span class="badge text-bg-light">{{ $domain->hotspots_count }} hotspots</span><span class="badge text-bg-light">{{ $domain->user_domain_points_count }} learners</span></div>
                            <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Tier</th><th>Points</th><th>Icon</th><th>Order</th><th></th></tr></thead><tbody>
                                @forelse ($domain->rankTiers as $tier)
                                    <tr><form method="POST" action="{{ route('admin.dashboard.library.domain-rank-tiers.update', $tier) }}">@csrf @method('PUT')<td><input name="name" value="{{ $tier->name }}" class="form-control form-control-sm" required></td><td><input name="min_points" value="{{ $tier->min_points }}" type="number" min="0" class="form-control form-control-sm" required></td><td><input name="badge_icon" value="{{ $tier->badge_icon }}" class="form-control form-control-sm"></td><td><input name="sort_order" value="{{ $tier->sort_order }}" type="number" min="0" class="form-control form-control-sm" required></td><td class="text-end"><button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-save"></i></button></td></form></tr>
                                @empty
                                    <tr><td colspan="5" class="text-body-secondary text-center py-3">No rank tiers yet.</td></tr>
                                @endforelse
                                <tr><form method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.rank-tiers.store', $domain) }}">@csrf<td><input name="name" class="form-control form-control-sm" placeholder="New tier" required></td><td><input name="min_points" type="number" min="0" value="0" class="form-control form-control-sm" required></td><td><input name="badge_icon" class="form-control form-control-sm" placeholder="bi-award"></td><td><input name="sort_order" type="number" min="0" value="{{ $domain->rankTiers->count() + 1 }}" class="form-control form-control-sm" required></td><td class="text-end"><button class="btn btn-sm btn-success" type="submit"><i class="bi bi-plus-circle"></i></button></td></form></tr>
                            </tbody></table></div>
                        </div>
                    </div>
                @empty
                    <div class="card"><div class="card-body text-center text-body-secondary py-5">No knowledge domains configured.</div></div>
                @endforelse
                @if ($domains->hasPages())<div class="mt-3">{{ $domains->links() }}</div>@endif
            </div>
            <div class="col-lg-4"><div class="card card-outline card-success"><div class="card-header"><h3 class="card-title mb-0">Create Domain</h3></div><form method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.store') }}">@csrf<div class="card-body"><div class="mb-3"><label class="form-label" for="name">Name</label><input id="name" name="name" class="form-control" required></div><div class="mb-3"><label class="form-label" for="slug">Slug</label><input id="slug" name="slug" class="form-control"></div><div class="mb-3"><label class="form-label" for="status">Status</label><select id="status" name="status" class="form-select">@foreach ($statuses as $status)<option value="{{ $status }}">{{ Str::headline($status) }}</option>@endforeach</select></div><div><label class="form-label" for="description">Description</label><textarea id="description" name="description" rows="4" class="form-control"></textarea></div></div><div class="card-footer"><button class="btn btn-success" type="submit"><i class="bi bi-plus-circle me-1"></i>Create</button></div></form></div></div>
        </div>
    </div></div>
@endsection