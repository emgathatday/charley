@extends('layouts.master')

@section('title', 'Edit Knowledge Domain')

@section('content_header')
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-6"><h1 class="mb-0">Edit Knowledge Domain</h1></div><div class="col-sm-6"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}">Knowledge Domains</a></li><li class="breadcrumb-item active" aria-current="page">Edit</li></ol></div></div></div></div>
@endsection

@section('content')
    <div class="app-content"><div class="container-fluid">
        @include('templates.components.alert-session')

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
            <div><h2 class="h4 mb-1">{{ $domain->name }}</h2><div class="text-body-secondary"><code>{{ $domain->slug }}</code> - quiz questions managed inside nested domain screens only</div></div>
            <div class="d-flex gap-2"><a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a><a href="{{ route('admin.dashboard.library.knowledge-domains.questions.create', $domain) }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>New Question</a></div>
        </div>

        <form method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.update', $domain) }}">
            @csrf
            @method('PUT')
            @include('admin.library.knowledge-domains._form')
            <div class="d-flex justify-content-end gap-2 mb-3"><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Domain</button></div>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-question-circle"></i></span><div class="info-box-content"><span class="info-box-text">Total Questions</span><span class="info-box-number">{{ $domain->quizQuestions->count() }}</span></div></div></div>
            <div class="col-md-4"><div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-check2-circle"></i></span><div class="info-box-content"><span class="info-box-text">Active</span><span class="info-box-number">{{ $domain->quizQuestions->where('status', 'active')->count() }}</span></div></div></div>
            <div class="col-md-4"><div class="info-box"><span class="info-box-icon text-bg-warning"><i class="bi bi-pencil"></i></span><div class="info-box-content"><span class="info-box-text">Draft</span><span class="info-box-number">{{ $domain->quizQuestions->where('status', 'draft')->count() }}</span></div></div></div>
        </div>

        <div class="card card-outline card-primary mb-3">
            <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Embedded Quiz Question Manager</h3><a href="{{ route('admin.dashboard.library.knowledge-domains.questions.create', $domain) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Question</a></div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Question</th><th>Status</th><th>Choices</th><th>Sort</th><th class="text-end">Actions</th></tr></thead><tbody>
                @forelse ($domain->quizQuestions as $question)
                    <tr>
                        <td><a class="fw-semibold text-decoration-none" href="{{ route('admin.dashboard.library.knowledge-domains.questions.edit', [$domain, $question]) }}">{{ $question->question_text }}</a><div class="small text-body-secondary">{{ Str::limit($question->explanation, 96) }}</div></td>
                        <td><span class="badge text-bg-{{ $question->status === 'active' ? 'success' : ($question->status === 'draft' ? 'warning' : 'secondary') }}">{{ Str::headline($question->status) }}</span></td>
                        <td><span class="badge text-bg-info">{{ $question->choices->count() }} options</span></td>
                        <td>{{ $question->sort_order ?? 0 }}</td>
                        <td class="text-end"><div class="d-flex justify-content-end gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.dashboard.library.knowledge-domains.questions.edit', [$domain, $question]) }}"><i class="bi bi-pencil-square"></i></a><form method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.questions.clone', [$domain, $question]) }}">@csrf<button class="btn btn-sm btn-outline-info" type="submit"><i class="bi bi-copy"></i></button></form><form method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.questions.destroy', [$domain, $question]) }}" onsubmit="return confirm('Delete this question?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-body-secondary py-4">No questions yet. Create the first question in the nested question screen.</td></tr>
                @endforelse
            </tbody></table></div></div>
        </div>
    </div></div>
@endsection
