@extends('layouts.master')

@section('title', 'Library Preview')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Media Preview</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item"><a href="{{ route('library.items.show', $item) }}">{{ Str::limit($item->title, 28) }}</a></li><li class="breadcrumb-item active">Preview</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid"><div class="card card-outline card-primary"><div class="card-header d-flex justify-content-between"><h3 class="card-title mb-0">{{ $item->title }}</h3><span class="badge text-bg-{{ $requiresWatermark ? 'warning' : 'success' }}">{{ $requiresWatermark ? 'Watermarked' : 'Direct preview' }}</span></div><div class="card-body"><div class="ratio ratio-16x9 bg-dark rounded d-flex align-items-center justify-content-center text-white"><div class="text-center"><i class="bi bi-play-btn display-3"></i><h2 class="h5 mt-2">Protected preview surface</h2><p class="mb-0 small">{{ $item->fileMedia?->streaming_url ?: $item->fileMedia?->original_name ?: 'No media file attached' }}</p></div></div></div><div class="card-footer d-flex gap-2"><a href="{{ route('library.items.show', ['libraryItem' => $item, 'partner_tier' => $partnerTier]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>@auth<form method="POST" action="{{ route('library.items.download', $item) }}">@csrf<input type="hidden" name="partner_tier" value="{{ $partnerTier }}"><button type="submit" class="btn btn-primary" @disabled(! $canDownload)><i class="bi bi-download me-1"></i>Download</button></form>@endauth</div></div></div></div>
@endsection
