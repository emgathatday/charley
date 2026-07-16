@extends('layouts.master')

@section('title', 'QA Question Detail')

@section('content_header')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="mb-0">QA Question Detail</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.iam.users') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.qa.index') }}">QA</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Question Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="app-content">
        <div class="container-fluid">
            @include('templates.components.alert-session')
            @include('admin.qa.components.action-tabs')

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="text-body-secondary small mb-1">Question</div>
                                <h3 class="card-title mb-0">{{ $question['title'] }}</h3>
                            </div>
                            <span class="badge text-bg-{{ $question['status_color'] }} mt-1">{{ Str::headline($question['status']) }}</span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <span class="badge text-bg-info fs-6 px-3 py-2"><i class="bi bi-buildings me-1"></i>Plant Type: {{ $question['plant'] }}</span>
                                <span class="badge text-bg-light border"><i class="bi bi-calendar-week me-1"></i>{{ $question['theme'] }}</span>
                                <span class="text-body-secondary small"><i class="bi bi-clock me-1"></i>Created {{ $question['created_at'] }}</span>
                            </div>
                            <p>{{ $question['body'] }}</p>
                            <div class="small text-body-secondary">Domains: {{ $question['domains'] ?: 'No domain links' }}</div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Answers</h3></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>Answer</th><th>Author</th><th>Confidence</th><th>Featured</th><th class="text-end">Approval Controls</th></tr></thead>
                                    <tbody>
                                    @forelse ($questionAnswers as $answer)
                                        <tr>
                                            <td>{{ $answer['body'] }}</td>
                                            <td>{{ $answer['author'] }}</td>
                                            <td><span class="badge text-bg-info">{{ $answer['confidence'] }}</span></td>
                                            <td>{{ $answer['featured'] ? 'Yes' : 'No' }}</td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <form method="POST" action="{{ route('admin.dashboard.qa.answers.feature', $answer['id']) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="confidence_level" value="high">
                                                        <input type="hidden" name="admin_rank_order" value="1">
                                                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-star-fill"></i></button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.dashboard.qa.answers.unfeature', $answer['id']) }}" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-x-lg"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-muted text-center py-3" colspan="5">No answers available.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Author Profile</h3></div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle text-bg-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-person-vcard fs-4"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $question['author'] }}</div>
                                    <div class="text-body-secondary small">{{ $question['author_meta'] ?? 'Demo profile metadata' }}</div>
                                </div>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-5 text-body-secondary">Role</div>
                                <div class="col-7 fw-semibold">{{ $question['author_role'] ?? 'Community member' }}</div>
                                <div class="col-5 text-body-secondary">Email</div>
                                <div class="col-7 text-break">{{ $question['author_email'] ?? 'No email recorded' }}</div>
                                <div class="col-5 text-body-secondary">Created</div>
                                <div class="col-7">{{ $question['created_at'] }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Question Status</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.dashboard.qa.questions.demo-status', $question['id']) }}" class="row g-3">
                                @csrf
                                <div class="col-12">
                                    <div class="form-label">Moderation state</div>
                                    <div class="btn-group w-100" role="group" aria-label="Question status segmented controls">
                                        @foreach (['active' => 'Active', 'draft' => 'Draft', 'unactive' => 'Unactive'] as $status => $label)
                                            <input type="radio" class="btn-check" name="status" id="question_status_{{ $status }}" value="{{ $status }}" autocomplete="off" @checked($question['status'] === $status)>
                                            <label class="btn btn-outline-primary" for="question_status_{{ $status }}">{{ $label }}</label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save Status</button></div>
                            </form>
                        </div>
                    </div>

                    <div class="card card-outline card-info mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Theme Assignment</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="text-body-secondary small d-block">Current weekly theme</span>
                                <span class="badge text-bg-info">{{ $question['theme'] }}</span>
                            </div>
                            <form method="POST" action="{{ route('admin.dashboard.qa.weekly-themes.assign-question', $themes->first()->id ?? 9301) }}" class="row g-3 align-items-end">
                                @csrf
                                <input type="hidden" name="question_id" value="{{ $question['id'] }}">
                                <div class="col-12">
                                    <label class="form-label" for="detail_theme_id">Move question to theme</label>
                                    <select id="detail_theme_id" class="form-select" onchange="this.form.action=this.options[this.selectedIndex].dataset.action">
                                        @foreach ($themes as $theme)
                                            <option value="{{ $theme->id }}" data-action="{{ route('admin.dashboard.qa.weekly-themes.assign-question', $theme->id) }}" @selected(($question['weekly_theme_id'] ?? null) == $theme->id)>{{ $theme->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-link-45deg me-1"></i>Assign Weekly Theme</button></div>
                            </form>
                        </div>
                    </div>

                    <div class="card card-warning mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Send Warning</h3></div>
                        <div class="card-body">
                            <form class="row g-3 align-items-end" method="POST">
                                @csrf
                                <div class="col-12"><label class="form-label" for="warning_message">Warning message</label><textarea id="warning_message" class="form-control" rows="4">Please add operating context before this question can be restored.</textarea></div>
                                <div class="col-12"><button class="btn btn-warning" type="button"><i class="bi bi-send me-1"></i>Send Demo Warning</button></div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title mb-0">Status Warning History</h3></div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <tbody>
                                @foreach ($warningHistory as $history)
                                    <tr><td><div class="fw-semibold">{{ $history['date'] }} - {{ Str::headline($history['status']) }}</div><div class="small text-body-secondary">{{ $history['note'] }}</div></td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
