@extends('layouts.master')

@section('title', 'Library')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6"><h1 class="mb-0">Library</h1></div>
                <div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('feed.index') }}">Feed</a></li><li class="breadcrumb-item active" aria-current="page">Library</li></ol></div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card card-outline card-primary h-100">
                    <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Browse approved Library</h3><span class="badge text-bg-success">Approved content only</span></div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('library.index') }}" class="row g-3 align-items-end">
                            <div class="col-md-4"><label for="search" class="form-label">Search</label><input id="search" class="form-control" name="search" placeholder="Document, media, source"></div>
                            <div class="col-md-2"><label for="category" class="form-label">Category</label><select id="category" class="form-select"><option>All</option>@foreach($categories as $category)<option>{{ $category }}</option>@endforeach</select></div>
                            <div class="col-md-2"><label for="plant_type" class="form-label">Plant Type</label><select id="plant_type" class="form-select"><option>All</option>@foreach($plantTypes as $plantType)<option>{{ $plantType }}</option>@endforeach</select></div>
                            <div class="col-md-2"><label for="domain" class="form-label">Domain</label><select id="domain" class="form-select"><option>All</option>@foreach($domains as $domain)<option>{{ $domain['name'] }}</option>@endforeach</select></div>
                            <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button><a href="{{ route('library.index') }}" class="btn btn-outline-secondary">Reset</a></div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-outline card-info h-100"><div class="card-header"><h3 class="card-title mb-0">Visual States</h3></div><div class="card-body"><div class="d-flex flex-column gap-2"><span class="badge text-bg-light text-start"><i class="bi bi-arrow-repeat me-1"></i>{{ $uiStates['loading'] }}</span><span class="badge text-bg-light text-start"><i class="bi bi-inbox me-1"></i>{{ $uiStates['empty'] }}</span><span class="badge text-bg-danger text-start"><i class="bi bi-exclamation-triangle me-1"></i>{{ $uiStates['error'] }}</span><span class="badge text-bg-warning text-start"><i class="bi bi-hourglass-split me-1"></i>{{ $uiStates['cooldown'] }}</span></div></div></div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-5">
                <div class="card card-outline card-success h-100">
                    <div class="card-header"><h3 class="card-title mb-0">Library Items</h3></div>
                    <div class="card-body p-0"><div class="list-group list-group-flush">
                        @forelse($items as $item)
                            <a href="#item-{{ $item['id'] }}" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between gap-2 mb-2"><span class="fw-semibold">{{ $item['title'] }}</span><span class="badge text-bg-{{ $item['download_allowed'] ? 'success' : 'secondary' }}">{{ $item['download_allowed'] ? 'Download' : 'View only' }}</span></div>
                                <div class="small text-body-secondary mb-2">{{ $item['summary'] }}</div>
                                <div class="d-flex flex-wrap gap-1"><span class="badge text-bg-light">{{ $item['category'] }}</span><span class="badge text-bg-light">{{ $item['plant_type'] }}</span><span class="badge text-bg-info">{{ $item['domain'] }}</span><span class="badge text-bg-primary">{{ Str::headline($item['content_type']) }}</span></div>
                            </a>
                        @empty
                            <div class="list-group-item text-center text-body-secondary py-5">{{ $uiStates['empty'] }}</div>
                        @endforelse
                    </div></div>
                </div>
            </div>

            <div class="col-xl-7">
                <div id="item-{{ $selectedItem['id'] }}" class="card card-outline card-primary h-100">
                    <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Document / Media Viewer</h3><span class="badge text-bg-success">Approved {{ $selectedItem['approved_at'] }}</span></div>
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                            <div><h2 class="h4 mb-1">{{ $selectedItem['title'] }}</h2><div class="text-body-secondary"><code>{{ $selectedItem['file_media_id'] }}</code> · {{ $selectedItem['file_label'] }}</div></div>
                            <div class="d-flex gap-2"><button class="btn btn-outline-primary" type="button"><i class="bi bi-eye me-1"></i>Record View</button><button class="btn btn-{{ $selectedItem['download_allowed'] ? 'primary' : 'secondary' }}" type="button" @disabled(! $selectedItem['download_allowed'])><i class="bi bi-download me-1"></i>Download</button></div>
                        </div>
                        <div class="border rounded bg-body-tertiary p-4 mb-3">
                            <div class="ratio ratio-16x9 bg-white rounded d-flex align-items-center justify-content-center text-center p-4">
                                <div><i class="bi bi-file-earmark-richtext display-4 text-primary"></i><h3 class="h5 mt-3">Protected {{ Str::headline($selectedItem['content_type']) }} preview</h3><p class="text-body-secondary mb-0">Watermark, streaming, and download controls resolve through media_files id {{ $selectedItem['file_media_id'] }}.</p></div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4"><div class="border rounded p-3"><div class="text-body-secondary small">Access</div><div class="fw-semibold">{{ Str::headline($selectedItem['access_level']) }}</div></div></div>
                            <div class="col-md-4"><div class="border rounded p-3"><div class="text-body-secondary small">Restrictions</div><div class="fw-semibold">Copy {{ $selectedItem['copy_paste_disabled'] ? 'blocked' : 'allowed' }}</div></div></div>
                            <div class="col-md-4"><div class="border rounded p-3"><div class="text-body-secondary small">Usage</div><div class="fw-semibold">{{ number_format($selectedItem['view_count']) }} views · {{ number_format($selectedItem['download_count']) }} downloads</div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card card-outline card-warning h-100">
                    <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Domain Quiz</h3><button class="btn btn-warning btn-sm" type="button"><i class="bi bi-play-circle me-1"></i>Start Quiz</button></div>
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3"><div><h2 class="h4 mb-1">{{ $selectedDomain['name'] }}</h2><div class="text-body-secondary">{{ $selectedDomain['question_count'] }} questions · {{ $selectedDomain['passed_count'] }} passed domains toward rank</div></div>@if($selectedDomain['cooldown_until'])<span class="badge text-bg-warning align-self-start">Cooldown until {{ $selectedDomain['cooldown_until'] }}</span>@else<span class="badge text-bg-success align-self-start">Available now</span>@endif</div>
                        @foreach($selectedDomain['questions'] as $index => $question)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between gap-2 mb-2"><div class="fw-semibold">Q{{ $index + 1 }}. {{ $question['text'] }}</div><span class="badge text-bg-{{ $question['difficulty'] === 'hard' ? 'danger' : 'warning' }}">{{ Str::headline($question['difficulty']) }}</span></div>
                                <div class="d-flex flex-column gap-2">@foreach($question['choices'] as $choice)<label class="border rounded p-2 mb-0"><input class="form-check-input me-2" type="radio" name="question_{{ $index }}" @checked($choice['correct'])> {{ $choice['text'] }} @if($choice['correct'])<span class="badge text-bg-success ms-2">Demo correct</span>@endif</label>@endforeach</div>
                                <div class="alert alert-info mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>{{ $question['explanation'] }}</div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end gap-2"><button class="btn btn-outline-secondary" type="button">Save Draft</button><button class="btn btn-primary" type="button">Submit Attempt</button></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card card-outline card-info mb-3"><div class="card-header"><h3 class="card-title mb-0">Attempt Result</h3></div><div class="card-body"><div class="d-flex align-items-center justify-content-between"><div><div class="text-body-secondary small">Latest Score</div><div class="display-6 fw-semibold">{{ $selectedDomain['last_score'] }}%</div></div><span class="badge text-bg-{{ $selectedDomain['last_score'] >= 80 ? 'success' : 'warning' }}">{{ $selectedDomain['last_score'] >= 80 ? 'Passed' : 'Cooldown' }}</span></div><div class="progress mt-3"><div class="progress-bar bg-{{ $selectedDomain['last_score'] >= 80 ? 'success' : 'warning' }}" style="width: {{ $selectedDomain['last_score'] }}%"></div></div></div></div>
                <div class="card card-outline card-secondary"><div class="card-header"><h3 class="card-title mb-0">Attempt History</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Domain</th><th>Score</th><th>Result</th><th>Next</th></tr></thead><tbody>@foreach($attemptHistory as $attempt)<tr><td>{{ $attempt['domain'] }}<div class="small text-body-secondary">{{ $attempt['submitted_at'] }}</div></td><td>{{ $attempt['score'] }}%</td><td><span class="badge text-bg-{{ $attempt['result'] === 'Passed' ? 'success' : 'warning' }}">{{ $attempt['result'] }}</span></td><td>{{ $attempt['next_attempt'] ?? 'Anytime' }}</td></tr>@endforeach</tbody></table></div></div></div>
            </div>
        </div>
    </div></div>
@endsection