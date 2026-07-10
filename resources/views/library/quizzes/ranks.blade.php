@extends('layouts.master')

@section('title', 'My Domain Ranks')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">My Domain Ranks</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('library.quizzes.index') }}">Quizzes</a></li><li class="breadcrumb-item active">Ranks</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid"><div class="row g-3">
        @foreach ($domains as $domain)
            @php($point = $domainPoints->firstWhere('knowledge_domain_id', $domain->id))
            <div class="col-md-6 col-xl-4"><div class="card h-100 card-outline card-primary"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><h2 class="h5">{{ $domain->name }}</h2><span class="badge text-bg-{{ $point ? 'success' : 'secondary' }}">{{ $point?->total_points ?? 0 }} pts</span></div><p class="text-body-secondary small">{{ Str::limit($domain->description, 120) }}</p><div class="d-flex flex-wrap gap-2">@foreach ($domain->rankTiers as $tier)<span class="badge text-bg-{{ $point && $point->current_rank_tier_id === $tier->id ? 'success' : 'light' }}">{{ $tier->badge_icon ? $tier->badge_icon.' ' : '' }}{{ $tier->name }} · {{ $tier->min_points }}</span>@endforeach</div></div></div></div>
        @endforeach
    </div></div></div>
@endsection