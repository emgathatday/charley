@extends('layouts.rebuild-dashboard')

@section('title', 'Knowledge Domain Detail')

@section('content')
    @php
        $questions = $domain->quizQuestions ?? collect();
        $totalQuestions = $questions->count();
        $activeQuestions = $questions->where('status', 'active')->count();
        $draftQuestions = $questions->where('status', 'draft')->count();
        $plantLabel = $domain->plantTypes->pluck('name')->join(', ') ?: ($domain->plantType?->name ?? 'General');
        $activeValue = (string) old('is_active', (int) $domain->is_active) === '1';
        $previewTotal = old('total_question_count', $domain->total_question_count ?: $totalQuestions);
        $previewPerAttempt = old('quiz_question_count', $domain->quiz_question_count);
    @endphp

    @include('templates.components.alert-session')

    <a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}" class="back-link">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
        Back to Knowledge Domains
    </a>

    <form id="knowledge-domain-form" method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.update', $domain) }}">
        @csrf
        @method('PUT')

        <div class="page-head">
            <div class="page-head-left">
                <div class="company-logo knowledge-domain-logo">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-platform-settings-ai-assistant"></use></svg>
                </div>
                <div>
                    <div class="page-title-row">
                        <div class="page-title">{{ old('name', $domain->name) }}</div>
                        <span @class(['badge', $activeValue ? 'knowledge-badge-active' : 'knowledge-badge-muted'])>{{ $activeValue ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="page-sub"><span>knowledge_domains #{{ $domain->id }}</span><span class="sep"></span><span>slug: {{ old('slug', $domain->slug) }}</span><span class="sep"></span><span>plant: {{ $plantLabel }}</span></div>
                </div>
            </div>
        </div>

        <div class="knowledge-create-layout">
            <div class="knowledge-create-main">
                @include('admin.library.knowledge-domains._form')

                <div class="table-wrap knowledge-question-table-wrap" id="questions">
                    <div class="table-header table-head-panel">
                        <div class="table-head-main">
                            <div class="table-title">Questions</div>
                            <div class="table-meta">{{ $totalQuestions }} questions attached to this knowledge domain.</div>
                        </div>
                        <div class="table-head-actions">
                            <select class="filter-select"><option>All Difficulty</option><option>easy</option><option>medium</option><option>hard</option></select>
                            <select class="filter-select"><option>All Statuses</option><option>active</option><option>draft</option><option>archived</option></select>
                            <button class="btn-outline btn-filter" type="button"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter-account-acc"></use></svg>Filter</button>
                            <button class="btn btn-outline btn-sm" type="button">Import</button>
                            <a href="{{ route('admin.dashboard.library.knowledge-domains.questions.create', $domain) }}" class="btn btn-primary btn-sm">Add Question</a>
                        </div>
                    </div>
                    <div class="table-scroll">
                        <table class="knowledge-question-table">
                            <thead><tr><th class="knowledge-check-col"><input class="knowledge-check-input" type="checkbox"></th><th>Question</th><th>Difficulty</th><th>Choices</th><th>Image</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
                            <tbody>
                                @forelse ($questions as $question)
                                    <tr>
                                        <td><input class="knowledge-check-input" type="checkbox"></td>
                                        <td>
                                            <div class="knowledge-question-title">
                                                <strong>{{ $question->question_text }}</strong>
                                                <span>{{ $question->explanation ? Str::limit($question->explanation, 96) : 'No explanation added.' }}</span>
                                            </div>
                                        </td>
                                        <td><span class="badge knowledge-badge-info">{{ $question->difficulty_level ?? 'medium' }}</span></td>
                                        <td>{{ $question->choices->count() }} choices</td>
                                        <td><span @class(['badge', $question->question_image_media_id ? 'knowledge-badge-info' : 'knowledge-badge-muted'])>{{ $question->question_image_media_id ? 'Media' : 'None' }}</span></td>
                                        <td><span @class(['badge', $question->status === 'active' ? 'knowledge-badge-active' : ($question->status === 'draft' ? 'knowledge-badge-warning' : 'knowledge-badge-muted')])>{{ $question->status }}</span></td>
                                        <td>{{ optional($question->updated_at)->format('d M') ?? 'Not recorded' }}</td>
                                        <td>
                                            <div class="action-cell">
                                                <a href="{{ route('admin.dashboard.library.knowledge-domains.questions.edit', [$domain, $question]) }}" class="action-btn primary" aria-label="View question"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-1-204-views-svg-viewbox-0-0"></use></svg></a>
                                                <a href="{{ route('admin.dashboard.library.knowledge-domains.questions.edit', [$domain, $question]) }}" class="action-btn" aria-label="Edit question"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-note"></use></svg></a>
                                                <button class="action-btn danger" type="submit" form="delete-question-{{ $question->id }}" aria-label="Archive question"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-admin-actions-button-class-btn-btn-danger"></use></svg></button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8"><div class="knowledge-question-title"><strong>No questions yet.</strong><span>Create the first question in the nested question screen.</span></div></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination"><span class="page-info">Showing {{ $questions->count() }} of {{ $totalQuestions }} results</span><button class="page-btn active" type="button">1</button></div>
                </div>
            </div>

            <div class="knowledge-create-side">
                <div class="side-card">
                    <div class="card card-padded knowledge-preview-card">
                        <div class="card-title">Quiz settings</div>
                        <div class="knowledge-preview-metric"><span>Total question count</span><strong>{{ $previewTotal }}</strong></div>
                        <div class="knowledge-preview-metric"><span>Active questions</span><strong>{{ $activeQuestions }}</strong></div>
                        <div class="knowledge-preview-metric"><span>Draft questions</span><strong>{{ $draftQuestions }}</strong></div>
                        <div class="knowledge-preview-metric"><span>Per attempt</span><strong>{{ $previewPerAttempt }}</strong></div>
                        <div class="knowledge-switch-stack">
                            <div class="switch-row">
                                <div>
                                    <div class="sw-label">Visible in Library</div>
                                    <div class="sw-desc">Available in quiz and expertise flows.</div>
                                </div>
                                <input type="hidden" name="is_active" value="0">
                                <label class="switch">
                                    <input id="is_active_switch" type="checkbox" name="is_active" value="1" @checked($activeValue)>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="decision-actions">
                            <button class="btn btn-primary btn-block-spaced" type="submit">Save changes</button>
                            <a href="{{ route('admin.dashboard.library.knowledge-domains.questions.create', $domain) }}" class="btn btn-outline btn-block-spaced">Add question</a>
                            <a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}" class="btn btn-ghost btn-block-spaced">Cancel</a>
                        </div>
                        <div class="knowledge-warning-note">Question rows belong to quiz_questions and choices belong to quiz_question_choices. Prefer inactive/archive when historical quiz_attempts exist.</div>
                    </div>
                    <div class="card card-padded">
                        <div class="card-title">Related tables</div>
                        <div class="knowledge-schema-list"><span>quiz_questions</span><span>quiz_attempts</span><span>user_domain_expertise</span><span>mandatory_quiz_domains</span><span>rank_promotion_quiz_logs</span></div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @foreach ($questions as $question)
        <form id="delete-question-{{ $question->id }}" method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.questions.destroy', [$domain, $question]) }}" onsubmit="return confirm('Delete this question?');">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endsection
