@extends('layouts.rebuild-dashboard')

@section('title', 'Create Knowledge Domain')

@section('content')
    @php
        $previewTotal = old('total_question_count', $domain->total_question_count ?? 0);
        $previewPerAttempt = old('quiz_question_count', $domain->quiz_question_count ?? 50);
        $activeValue = (string) old('is_active', (int) ($domain->is_active ?? true));
    @endphp

    @include('templates.components.alert-session')

    <a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}" class="back-link">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
        Back to Knowledge Domains
    </a>

    <form id="knowledge-domain-form" method="POST" action="{{ route('admin.dashboard.library.knowledge-domains.store') }}">
        @csrf

        <div class="page-head">
            <div class="page-head-left">
                <div class="company-logo knowledge-domain-logo">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-platform-settings-ai-assistant"></use></svg>
                </div>
                <div>
                    <div class="page-title-row">
                        <div class="page-title">Create Knowledge Domain</div>
                        <span class="badge knowledge-badge-warning">Draft setup</span>
                    </div>
                    <div class="page-sub"><span>Source: knowledge_domains</span><span class="sep"></span><span>Questions saved after domain creation</span></div>
                </div>
            </div>
        </div>

        <div class="knowledge-create-layout">
            <div class="knowledge-create-main">
                @include('admin.library.knowledge-domains._form')

                <div class="table-wrap knowledge-question-table-wrap" id="questions">
                    <div class="table-header table-head-panel">
                        <div class="table-head-main">
                            <div class="table-title">Question seed list</div>
                            <div class="table-meta">The domain must be created before questions can be added.</div>
                        </div>
                    </div>
                    <div class="table-scroll">
                        <table class="knowledge-question-table">
                            <thead><tr><th>Question setup</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="knowledge-question-title">
                                            <strong>Create this knowledge domain first.</strong>
                                            <span>After the domain exists, questions and answer choices can be managed from the edit screen.</span>
                                        </div>
                                    </td>
                                    <td><span class="badge knowledge-badge-muted">Waiting for domain</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="knowledge-create-side">
                <div class="side-card">
                    <div class="card card-padded knowledge-preview-card">
                        <div class="card-title">Quiz settings</div>
                        <div class="knowledge-preview-metric"><span>Total question count</span><strong>{{ $previewTotal }}</strong></div>
                        <div class="knowledge-preview-metric"><span>Per attempt</span><strong data-quiz-question-preview>{{ $previewPerAttempt }}</strong></div>
                        <div class="knowledge-preview-metric"><span>Pass threshold</span><strong>from quiz_attempts snapshot</strong></div>
                        <div class="knowledge-switch-stack">
                            <div class="switch-row">
                                <div>
                                    <div class="sw-label">Visible in Library</div>
                                    <div class="sw-desc">Available in quiz and expertise flows after creation.</div>
                                </div>
                                <input type="hidden" name="is_active" value="0">
                                <label class="switch">
                                    <input id="is_active_switch" type="checkbox" name="is_active" value="1" @checked($activeValue === '1')>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="decision-actions">
                            <a href="{{ route('admin.dashboard.library.knowledge-domains.index') }}" class="btn btn-outline btn-block-spaced">Cancel</a>
                            <button class="btn btn-primary btn-block-spaced" type="submit">Create Domain</button>
                        </div>
                        <div class="knowledge-warning-note">Question rows belong to quiz_questions and choices belong to quiz_question_choices. The domain form only creates knowledge_domains.</div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            const perAttemptInput = document.getElementById('quiz_question_count');
            const perAttemptPreview = document.querySelector('[data-quiz-question-preview]');

            if (! nameInput || ! slugInput) {
                return;
            }

            let slugWasEdited = slugInput.value.trim() !== '';

            const slugify = (value) => value
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            slugInput.addEventListener('input', () => {
                slugWasEdited = true;
            });

            nameInput.addEventListener('input', () => {
                if (! slugWasEdited) {
                    slugInput.value = slugify(nameInput.value);
                }
            });

            perAttemptInput?.addEventListener('input', () => {
                if (perAttemptPreview) {
                    perAttemptPreview.textContent = perAttemptInput.value || '0';
                }
            });
        });
    </script>
@endsection
