@extends('qa.layout')

@section('title', 'Q&A Community')

@section('header')
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="m-0">Q&A Community</h1>
        </div>
        <div class="col-md-4 text-md-right mt-3 mt-md-0">
            <a href="{{ route('qa.community.ask') }}" class="btn btn-primary"><i class="bi bi-plus-circle mr-1"></i> Ask Question</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            @include('qa.components.filter-panel', [
                'plantTypes' => $plantTypes,
                'weeklyThemes' => $weeklyThemes,
                'filters' => $filters ?? [],
            ])

            @forelse ($questions as $question)
                @include('qa.components.question-card', ['question' => $question])
            @empty
                <div class="card">
                    <div class="card-body text-center text-muted">
                        No published questions are available yet.
                    </div>
                </div>
            @endforelse
        </div>
        <div class="col-lg-4">
            @include('qa.components.leaderboard', ['leaders' => $leaders ?? []])
        </div>
    </div>
@endsection