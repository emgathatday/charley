@extends('layouts.master')

@section('title', 'Library Quizzes')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Library Quizzes</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.index') }}">Library</a></li><li class="breadcrumb-item active">Quizzes</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-ui-checks"></i></span><div class="info-box-content"><span class="info-box-text">Quizzes</span><span class="info-box-number">{{ $stats['quizzes'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-check2-circle"></i></span><div class="info-box-content"><span class="info-box-text">Published</span><span class="info-box-number">{{ $stats['published'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-info"><i class="bi bi-question-circle"></i></span><div class="info-box-content"><span class="info-box-text">Questions</span><span class="info-box-number">{{ $stats['questions'] }}</span></div></div></div>
            <div class="col-md-3"><div class="info-box"><span class="info-box-icon text-bg-warning"><i class="bi bi-clipboard-check"></i></span><div class="info-box-content"><span class="info-box-text">Attempts</span><span class="info-box-number">{{ $stats['attempts'] }}</span></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-8"><div class="card card-outline card-primary"><div class="card-header"><h3 class="card-title mb-0">Quiz Catalog</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Quiz</th><th>Domain</th><th>Questions</th><th>Attempts</th><th>Status</th></tr></thead><tbody>
                @forelse ($quizzes as $quiz)
                    <tr><td><a href="{{ route('admin.dashboard.library.quizzes.show', $quiz) }}" class="fw-semibold text-decoration-none">{{ $quiz->title }}</a><div class="small text-body-secondary">{{ $quiz->slug }}</div></td><td>{{ $quiz->knowledgeDomain?->name ?? '-' }}</td><td>{{ $quiz->questions_count }}</td><td>{{ $quiz->attempts_count }}</td><td><span class="badge text-bg-{{ $quiz->status === 'published' ? 'success' : 'secondary' }}">{{ Str::headline($quiz->status) }}</span></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-body-secondary py-4">No quizzes configured.</td></tr>
                @endforelse
            </tbody></table></div></div>@if ($quizzes->hasPages())<div class="card-footer">{{ $quizzes->links() }}</div>@endif</div></div>
            <div class="col-lg-4"><div class="card card-outline card-success"><div class="card-header"><h3 class="card-title mb-0">Create Quiz</h3></div><form method="POST" action="{{ route('admin.dashboard.library.quizzes.store') }}">@csrf<div class="card-body"><div class="mb-3"><label class="form-label" for="title">Title</label><input id="title" name="title" class="form-control" required></div><div class="mb-3"><label class="form-label" for="knowledge_domain_id">Knowledge Domain</label><select id="knowledge_domain_id" name="knowledge_domain_id" class="form-select" required>@foreach ($domains as $domain)<option value="{{ $domain->id }}">{{ $domain->name }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label" for="description">Description</label><textarea id="description" name="description" rows="3" class="form-control"></textarea></div><div class="row g-2"><div class="col-6"><label class="form-label" for="time_limit_minutes">Minutes</label><input id="time_limit_minutes" name="time_limit_minutes" type="number" min="1" class="form-control"></div><div class="col-6"><label class="form-label" for="max_attempts_per_user">Max Attempts</label><input id="max_attempts_per_user" name="max_attempts_per_user" type="number" min="1" class="form-control"></div></div><div class="mt-3"><label class="form-label" for="status">Status</label><select id="status" name="status" class="form-select">@foreach ($statuses as $status)<option value="{{ $status }}">{{ Str::headline($status) }}</option>@endforeach</select></div></div><div class="card-footer"><button class="btn btn-success" type="submit"><i class="bi bi-plus-circle me-1"></i>Create</button></div></form></div></div>
        </div>
    </div></div>
@endsection