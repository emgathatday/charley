@extends('layouts.master')

@section('title', 'Edit Ranks Tier')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Edit Ranks Tier</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.rank-tiers.index') }}">Ranks Tiers</a></li><li class="breadcrumb-item active" aria-current="page">Edit</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div><h2 class="h4 mb-1">{{ $rankTier->name }}</h2><div class="text-body-secondary"><code>{{ $rankTier->slug }}</code> - rank order #{{ $rankTier->rank_order }}</div></div>
            <a href="{{ route('admin.dashboard.library.rank-tiers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
        <form method="POST" action="{{ route('admin.dashboard.library.rank-tiers.update', $rankTier) }}">
            @csrf
            @method('PUT')
            @include('admin.library.rank-tiers._form')
            <div class="d-flex justify-content-end gap-2 mb-3"><a href="{{ route('admin.dashboard.library.rank-tiers.index') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button></div>
        </form>
    </div></div>
@endsection