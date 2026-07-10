@extends('layouts.master')

@section('title', 'Library Quizzes')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Library Quizzes</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('library.index') }}">Library</a></li><li class="breadcrumb-item active">Quizzes</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><form method="GET" class="d-flex gap-2"><select name="domain" class="form-select"><option value="">All domains</option>@foreach ($domains as $domain)<option value="{{ $domain->id }}" @selected(($filters['domain'] ?? '') == $domain->id)>{{ $domain->name }}</option>@endforeach</select><button class="btn btn-primary" type="submit"><i class="bi bi-filter"></i></button></form>@auth<a href="{{ route('library.domain-ranks.index') }}" class="btn btn-outline-primary"><i class="bi bi-award me-1"></i>My Domain Ranks</a>@endauth</div>
        <div class="row g-3">@forelse ($quizzes as $quiz)<div class="col-md-6 col-xl-4"><div class="card h-100 card-outline card-primary"><div class="card-body d-flex flex-column"><div class="d-flex justify-content-between gap-2 mb-2"><span class="badge text-bg-light">{{ $quiz->knowledgeDomain?->name ?? 'General' }}</span><span class="badge text-bg-info">{{ $quiz->questions_count }} questions</span></div><h2 class="h5">{{ $quiz->title }}</h2><p class="text-body-secondary small">{{ Str::limit($quiz->description, 140) }}</p>@php($point = $userPoints->get($quiz->knowledge_domain_id))<div class="small mb-3">@if ($point)<span class="badge text-bg-success">{{ $point->currentRankTier?->name ?? 'Rank pending' }} · {{ $point->total_points }} pts</span>@else<span class="badge text-bg-secondary">No rank yet</span>@endif</div><div class="mt-auto d-flex justify-content-between align-items-center"><span class="small text-body-secondary">{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : 'No timer' }}</span><a href="{{ route('library.quizzes.show', $quiz) }}" class="btn btn-sm btn-primary"><i class="bi bi-play-circle me-1"></i>Start</a></div></div></div></div>@empty<div class="col-12"><div class="card"><div class="card-body text-center text-body-secondary py-5">No published quizzes.</div></div></div>@endforelse</div>
        @if ($quizzes->hasPages())<div class="mt-3">{{ $quizzes->links() }}</div>@endif
    </div></div>
@endsection