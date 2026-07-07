@extends('layouts.master')

@section('title', 'Library Download')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Download Ready</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item"><a href="{{ route('library.items.show', $item) }}">{{ Str::limit($item->title, 28) }}</a></li><li class="breadcrumb-item active">Download</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid"><div class="card card-outline card-success"><div class="card-body"><div class="d-flex align-items-start gap-3"><span class="btn btn-success btn-lg disabled"><i class="bi bi-download"></i></span><div><h2 class="h4 mb-2">{{ $item->fileMedia?->original_name ?? $item->title }}</h2><p class="text-body-secondary mb-2">Download access has been logged. The protected file handoff can be connected to signed storage URLs in the media pipeline.</p><span class="badge text-bg-{{ $requiresWatermark ? 'warning' : 'success' }}">{{ $requiresWatermark ? 'Watermark required' : 'No watermark required' }}</span></div></div></div><div class="card-footer d-flex gap-2"><a href="{{ route('library.items.show', ['libraryItem' => $item, 'partner_tier' => $partnerTier]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to item</a><a href="{{ route('library.items.preview', ['libraryItem' => $item, 'partner_tier' => $partnerTier]) }}" class="btn btn-outline-primary"><i class="bi bi-eye me-1"></i>Preview</a></div></div></div></div>
@endsection
