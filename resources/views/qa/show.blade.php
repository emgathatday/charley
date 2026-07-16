@extends('qa.layout')

@section('title', $question['title'] ?? 'Question Detail')

@section('header')
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="m-0">Question Detail</h1>
        </div>
        <div class="col-md-4 text-md-right mt-3 mt-md-0">
            <a href="{{ route('qa.community.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left mr-1"></i> Back</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge badge-info">{{ $question['plant'] }}</span>
                        <span class="badge badge-light">{{ $question['theme'] }}</span>
                        @include('qa.components.anonymous-badge', ['anonymous' => $question['anonymous']])
                    </div>
                    <h2 class="h4">{{ $question['title'] }}</h2>
                    <p>{{ $question['body'] }}</p>
                    @include('qa.components.domain-links', ['domains' => $question['domains']])
                    @include('qa.components.media-list', ['media' => $question['media']])
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Answers</h3></div>
                <div class="card-body">
                    @forelse ($answers as $answer)
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $answer['author'] }}</strong>
                                @if ($answer['featured'])
                                    <span class="badge badge-success">Featured</span>
                                @endif
                            </div>
                            <p class="mb-1">{{ $answer['body'] }}</p>
                            @include('qa.components.media-list', ['media' => $answer['media'] ?? []])
                            <span class="text-muted">Confidence: {{ ucfirst($answer['confidence']) }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No answers have been posted yet.</p>
                    @endforelse
                </div>
            </div>

            @include('qa.components.answer-form', ['question' => $question])
        </div>
        <div class="col-lg-4">
            @include('qa.components.leaderboard', ['leaders' => $leaders ?? []])
        </div>
    </div>
@endsection