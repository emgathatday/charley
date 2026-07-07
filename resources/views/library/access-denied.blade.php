@extends('layouts.master')

@section('title', 'Library Access Denied')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Access Denied</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item active">Access Denied</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid"><div class="card card-outline card-danger"><div class="card-body text-center py-5"><i class="bi bi-shield-lock display-4 text-danger"></i><h2 class="h4 mt-3">This library item is protected</h2><p class="text-body-secondary mb-4">{{ $reason }}</p><div class="d-flex justify-content-center gap-2"><a href="{{ route('library.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Browse Library</a>@guest<a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Sign In</a>@endguest</div></div><div class="card-footer"><div class="fw-semibold">{{ $item->title }}</div><small class="text-body-secondary">Access level: {{ Str::headline($item->access_level) }}{{ $partnerTier ? ' · Tier: '.Str::headline($partnerTier) : '' }}</small></div></div></div></div>
@endsection
