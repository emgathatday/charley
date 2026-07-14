@extends('layouts.master')

@section('title', 'Ranks Tiers')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Ranks Tiers</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item active" aria-current="page">Ranks Tiers</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-award"></i></span><div class="info-box-content"><span class="info-box-text">Rank Tiers</span><span class="info-box-number">{{ $stats['tiers'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-check2-circle"></i></span><div class="info-box-content"><span class="info-box-text">Active</span><span class="info-box-number">{{ $stats['active_tiers'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-secondary"><i class="bi bi-pencil-square"></i></span><div class="info-box-content"><span class="info-box-text">Draft</span><span class="info-box-number">{{ $stats['draft_tiers'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-danger"><i class="bi bi-trash3"></i></span><div class="info-box-content"><span class="info-box-text">Deleted</span><span class="info-box-number">{{ $stats['deleted_tiers'] }}</span></div></div></div>
        </div>

        <div class="card card-outline card-primary mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h3 class="card-title mb-0">Ranks Tiers</h3>
                <div class="btn-group btn-group-sm" role="group" aria-label="Rank tier status filter">
                    @foreach(['active' => 'Active', 'draft' => 'Draft', 'deleted' => 'Deleted'] as $status => $label)
                        <a href="{{ route('admin.dashboard.library.rank-tiers.index', ['status' => $status]) }}" class="btn btn-{{ ($filters['status'] ?? 'active') === $status ? 'primary' : 'outline-primary' }}">
                            {{ $label }}
                            <span class="badge text-bg-light ms-1">{{ $statusCounts[$status] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Default Cap</th>
                                <th>Min Experience</th>
                                <th>Required Quizzes</th>
                                <th>Mandatory Quizzes</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rankTiers as $tier)
                                <tr>
                                    <td><div class="fw-semibold">{{ $tier->name }}</div><code>{{ $tier->slug }}</code></td>
                                    <td><span class="badge text-bg-dark">#{{ $tier->rank_order }}</span></td>
                                    <td><span class="badge text-bg-{{ $tier->status === 'active' ? 'success' : ($tier->status === 'draft' ? 'secondary' : 'danger') }}">{{ ucfirst($tier->status) }}</span></td>
                                    <td>{{ number_format((float) $tier->default_cap_percentage, 2) }}%</td>
                                    <td>{{ is_null($tier->min_years_experience) ? 'N/A' : number_format($tier->min_years_experience).' years' }}</td>
                                    <td>{{ number_format($tier->required_quiz_count) }}</td>
                                    <td>{{ number_format($tier->required_mandatory_quiz_count) }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                            <a href="{{ route('admin.dashboard.library.rank-tiers.edit', $tier->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                                            <form method="POST" action="{{ route('admin.dashboard.library.rank-tiers.clone', $tier) }}" onsubmit="return confirm('Clone this rank tier as a draft?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-files me-1"></i>Clone</button>
                                            </form>
                                            @if($tier->status !== 'active')
                                                <form method="POST" action="{{ route('admin.dashboard.library.rank-tiers.status', $tier) }}" onsubmit="return confirm('Move this rank tier to active?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check2-circle me-1"></i>Activate</button>
                                                </form>
                                            @endif
                                            @if($tier->status !== 'draft')
                                                <form method="POST" action="{{ route('admin.dashboard.library.rank-tiers.status', $tier) }}" onsubmit="return confirm('Move this rank tier to draft?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="draft">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Draft</button>
                                                </form>
                                            @endif
                                            @if($tier->status !== 'deleted')
                                                <form method="POST" action="{{ route('admin.dashboard.library.rank-tiers.destroy', $tier) }}" onsubmit="return confirm('Move this rank tier to deleted?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-body-secondary py-4">No rank tiers found for this status.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($rankTiers->hasPages())
                <div class="card-footer">{{ $rankTiers->links() }}</div>
            @endif
        </div>
    </div></div>
@endsection