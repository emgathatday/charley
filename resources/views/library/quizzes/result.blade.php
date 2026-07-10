@extends('layouts.master')

@section('title', 'Quiz Result')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Quiz Result</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('library.quizzes.index') }}">Quizzes</a></li><li class="breadcrumb-item active">Result</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        <div class="card card-outline card-success"><div class="card-body text-center py-5"><div class="display-6 mb-2">{{ $attempt->scorePercent() }}%</div><h2 class="h4">{{ $attempt->quiz?->title }}</h2><p class="text-body-secondary mb-3">{{ $attempt->score }}/{{ $attempt->max_possible_score }} points in {{ $attempt->quiz?->knowledgeDomain?->name ?? 'General' }}</p>@if ($domainPoint)<span class="badge text-bg-success fs-6">{{ $domainPoint->currentRankTier?->name ?? 'Rank pending' }} · {{ $domainPoint->total_points }} pts</span>@else<span class="badge text-bg-light">No domain points yet</span>@endif<div class="mt-4 d-flex justify-content-center gap-2"><a href="{{ route('library.quizzes.index') }}" class="btn btn-primary"><i class="bi bi-arrow-left me-1"></i>Back to Quizzes</a><a href="{{ route('library.domain-ranks.index') }}" class="btn btn-outline-primary"><i class="bi bi-award me-1"></i>Ranks</a></div></div></div>
    </div></div>
@endsection