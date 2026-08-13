@php
    $isEdit = $question->exists;
    $action = $isEdit
        ? route('admin.dashboard.library.knowledge-domains.questions.update', [$domain, $question])
        : route('admin.dashboard.library.knowledge-domains.questions.store', $domain);
    $rows = old('choices', $choiceRows);
    if (count($rows) < 2) {
        $rows = array_pad($rows, 2, ['choice_text' => '', 'explanation' => '', 'sort_order' => 0, 'is_correct' => false]);
    }
    $correctChoice = old('correct_choice');
    if ($correctChoice === null) {
        foreach ($rows as $index => $choiceRow) {
            if (! empty($choiceRow['is_correct'])) {
                $correctChoice = $index;
                break;
            }
        }
    }
    $correctChoice ??= 0;
    $title = $isEdit ? 'Edit Quiz Question' : 'Create Quiz Question';
    $submitText = $isEdit ? 'Update question' : 'Create question';
    $domainLabel = $domain->name;
    $questionTextValue = old('question_text', $question->question_text ?? '');
    $statusValue = old('status', $question->status ?? 'draft');
    $sortValue = old('sort_order', $question->sort_order ?? 0);
    $imageValue = old('question_image_media_id', $question->question_image_media_id);
    $explanationValue = old('explanation', $question->explanation ?? '');
    $difficultyValue = old('difficulty_level', $question->difficulty_level ?? 'medium');
    $questionTypeValue = old('question_type', $question->question_type ?? 'single_choice');
@endphp

<form method="POST" action="{{ $action }}" id="question-choice-form">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="correct_choice" id="correct_choice_value" value="{{ $correctChoice }}">

    <div class="page-head knowledge-hero">
        <div class="page-head-left">
            <div class="company-logo knowledge-domain-logo"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-quiz-and-question-bank"></use></svg></div>
            <div>
                <div class="page-title-row">
                    <div class="page-title">{{ $title }}</div>
                    <span @class(['badge', $statusValue === 'active' ? 'knowledge-badge-active' : ($statusValue === 'archived' ? 'knowledge-badge-muted' : 'knowledge-badge-warning')])>{{ Str::headline($statusValue) }}</span>
                    <span class="badge knowledge-badge-info">Child question</span>
                </div>
                <div class="page-sub"><span>quiz_questions</span><span class="sep"></span><span>Domain: {{ $domainLabel }}</span><span class="sep"></span><span>choices: quiz_question_choices</span></div>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" type="button">Save draft</button>
            <button class="btn btn-primary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-as-draft-svg-viewbox-0"></use></svg>{{ $submitText }}</button>
        </div>
    </div>

    <input type="hidden" value="{{ $domain->id }}" name="knowledge_domain_id">
    <div class="question-create-layout">
        <div class="question-create-main">
            <div class="form-card question-card">
                <div class="form-card-header">
                    <div class="form-card-icon blue"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-quiz-and-question-bank"></use></svg></div>
                    <div><div class="form-card-title">Question content</div><div class="form-card-sub">This question is created inside the current Knowledge Domain.</div></div>
                </div>
                <div class="form-card-body">
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        <div class="col">
                            <div class="field">
                                <label>Question mode <span class="req">*</span></label>
                                <label class="question-mode-toggle" for="questionModeToggle">
                                    <input type="checkbox" id="questionModeToggle" value="multiple_choice" @checked($questionTypeValue === 'multiple_choice')>
                                    <span class="question-mode-track"><strong>Single choice</strong><strong>Multi choice</strong></span>
                                </label>
                                <div class="field-hint">Single accepts one correct toggle. Multi allows more than one correct answer.</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="field"><label for="difficulty_level">Difficulty <span class="req">*</span></label><select id="difficulty_level" @class(['is-invalid' => $errors->has('difficulty_level')])><option value="easy" @selected($difficultyValue === 'easy')>easy</option><option value="medium" @selected($difficultyValue === 'medium')>medium</option><option value="hard" @selected($difficultyValue === 'hard')>hard</option></select>@error('difficulty_level')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        </div>
                        <div class="col">
                            <div class="field">
                                <label for="status">Status</label>
                                <select id="status" name="status" @class(['is-invalid' => $errors->has('status')])>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected($statusValue === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                                <div class="field-hint">Use draft until the correct answer and explanations are reviewed.</div>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="field">
                                <label for="question_image_media_id">Image media</label>
                                <div class="question-media-select"><input id="question_image_media_id" name="question_image_media_id" type="number" value="{{ $imageValue }}" placeholder="question_image_media_id" @class(['is-invalid' => $errors->has('question_image_media_id')])><button class="btn btn-outline btn-sm" type="button">Attach</button></div>
                                <div class="field-hint">Optional diagram from media_files.</div>
                                @error('question_image_media_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="field field-full">
                                <label for="question_text">Question text <span class="req">*</span></label>
                                <textarea id="question_text" name="question_text" class="question-textarea" @class(['is-invalid' => $errors->has('question_text')])>{{ $questionTextValue }}</textarea>
                                @error('question_text')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="field field-full">
                                <label for="question_explanation">Question explanation</label>
                                <textarea id="question_explanation" name="explanation" @class(['is-invalid' => $errors->has('explanation')])>{{ $explanationValue }}</textarea>
                                @error('explanation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="field">
                                <label for="question_sort_order">Sort order</label>
                                <input id="question_sort_order" name="sort_order" type="number" min="0" value="{{ $sortValue }}" @class(['is-invalid' => $errors->has('sort_order')]) required>
                                @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-wrap question-choice-wrap">
                <div class="table-header table-head-panel">
                    <div class="table-head-main"><div class="table-title">Answer choices</div><div class="table-meta">Use the correct toggle according to the stored single-answer contract.</div></div>
                    <div class="table-head-actions"><button class="btn btn-outline btn-sm" type="button">Reorder</button><button class="btn btn-primary btn-sm" type="button" id="add-choice">Add choice</button></div>
                </div>
                @error('choices')<div class="alert alert-danger">{{ $message }}</div>@enderror
                @error('correct_choice')<div class="alert alert-danger">{{ $message }}</div>@enderror
                <div class="question-choice-list" id="choice-list">
                    @foreach ($rows as $index => $choice)
                        @php
                            $key = chr(65 + $index);
                            $isCorrect = (string) $correctChoice === (string) $index;
                        @endphp
                        <div @class(['question-choice-row', 'is-correct' => $isCorrect]) data-choice-row>
                            <div class="question-choice-key" data-choice-key>{{ $key }}</div>
                            <div class="question-choice-body">
                                <div class="field"><label>Choice text</label><textarea name="choices[{{ $index }}][choice_text]" @class(['is-invalid' => $errors->has("choices.$index.choice_text")])>{{ $choice['choice_text'] ?? '' }}</textarea></div>
                                <div class="field"><label>Explanation</label><textarea name="choices[{{ $index }}][explanation]">{{ $choice['explanation'] ?? '' }}</textarea></div>
                            </div>
                            <div class="question-choice-controls">
                                <label class="question-correct-toggle"><input class="question-correct-input" type="checkbox" @checked($isCorrect)><span></span><strong>Correct</strong></label>
                                <input class="question-sort-input" name="choices[{{ $index }}][sort_order]" type="number" value="{{ $choice['sort_order'] ?? $index + 1 }}" aria-label="Sort order {{ $key }}">
                                <button type="button" class="btn btn-ghost btn-sm ann-delete-btn" data-remove-choice>Remove</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="question-create-side">
            <div class="side-card">
                <div class="card card-padded question-preview-card">
                    <div class="card-title">Question preview</div>
                    <div class="question-preview-domain">{{ $domainLabel }}</div>
                    <div class="question-preview-text" id="questionPreviewText">{{ $questionTextValue ?: 'Question text preview' }}</div>
                    <div class="question-preview-options" id="questionPreviewOptions"></div>
                </div>
                <div class="card card-padded">
                    <div class="card-title">Record mapping</div>
                    <div class="knowledge-schema-list"><span>quiz_questions</span><span>quiz_question_choices</span><span>media_files</span><span>users</span></div>
                    <div class="knowledge-warning-note">The parent knowledge_domain_id is inherited from this page context; only question_text, status and choices are edited here.</div>
                </div>
                <div class="card card-padded decision-panel">
                    <div class="card-title">Admin actions</div>
                    <div class="decision-actions"><button class="btn btn-primary btn-block-spaced" type="submit">{{ $submitText }}</button><button class="btn btn-outline btn-block-spaced" type="button">Save draft</button><a href="{{ route('admin.dashboard.library.knowledge-domains.edit', $domain) }}#questions" class="btn btn-ghost btn-block-spaced">Cancel</a></div>
                </div>
            </div>
        </div>
    </div>

    <div class="action-bar"><span class="action-bar-note">Required: knowledge_domain_id, question_text, difficulty_level, status and at least two choices.</span><div class="action-bar-right"><a href="{{ route('admin.dashboard.library.knowledge-domains.edit', $domain) }}#questions" class="btn btn-outline">Cancel</a><button class="btn btn-primary" type="submit">{{ $submitText }}</button></div></div>
</form>

<template id="choice-template">
    <div class="question-choice-row" data-choice-row>
        <div class="question-choice-key" data-choice-key>A</div>
        <div class="question-choice-body">
            <div class="field"><label>Choice text</label><textarea></textarea></div>
            <div class="field"><label>Explanation</label><textarea></textarea></div>
        </div>
        <div class="question-choice-controls">
            <label class="question-correct-toggle"><input class="question-correct-input" type="checkbox"><span></span><strong>Correct</strong></label>
            <input class="question-sort-input" type="number" value="1" aria-label="Sort order">
            <button type="button" class="btn btn-ghost btn-sm ann-delete-btn" data-remove-choice>Remove</button>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modeToggle = document.getElementById('questionModeToggle');
    const list = document.getElementById('choice-list');
    const template = document.getElementById('choice-template');
    const addButton = document.getElementById('add-choice');
    const correctValue = document.getElementById('correct_choice_value');
    const questionText = document.getElementById('question_text');
    const previewText = document.getElementById('questionPreviewText');
    const previewOptions = document.getElementById('questionPreviewOptions');

    function keyFor(index) {
        return String.fromCharCode(65 + index);
    }

    function rows() {
        return Array.from(list.querySelectorAll('[data-choice-row]'));
    }

    function syncChoiceRows(activeInput = null) {
        const isMulti = modeToggle && modeToggle.checked;
        if (activeInput && activeInput.checked && ! isMulti) {
            rows().forEach(function (row) {
                const input = row.querySelector('.question-correct-input');
                if (input !== activeInput) input.checked = false;
            });
        }

        let checkedIndex = rows().findIndex(function (row) {
            return row.querySelector('.question-correct-input').checked;
        });
        if (checkedIndex < 0 && rows().length) {
            checkedIndex = 0;
            rows()[0].querySelector('.question-correct-input').checked = true;
        }
        correctValue.value = checkedIndex;

        previewOptions.innerHTML = '';
        rows().forEach(function (row, index) {
            const key = keyFor(index);
            const textArea = row.querySelector('.question-choice-body textarea');
            const explanationArea = row.querySelectorAll('.question-choice-body textarea')[1];
            const sortInput = row.querySelector('.question-sort-input');
            const checkedInput = row.querySelector('.question-correct-input');
            const checked = checkedInput.checked;

            row.classList.toggle('is-correct', checked);
            row.querySelector('[data-choice-key]').textContent = key;
            textArea.name = `choices[${index}][choice_text]`;
            explanationArea.name = `choices[${index}][explanation]`;
            sortInput.name = `choices[${index}][sort_order]`;
            sortInput.setAttribute('aria-label', `Sort order ${key}`);
            if (! sortInput.value) sortInput.value = index + 1;

            const option = document.createElement('span');
            option.className = checked ? 'is-correct' : '';
            const label = textArea.value.trim() || 'Choice text preview';
            option.textContent = `${key}. ${label}`;
            previewOptions.appendChild(option);
        });
    }

    addButton.addEventListener('click', function () {
        list.appendChild(template.content.firstElementChild.cloneNode(true));
        syncChoiceRows();
    });

    list.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-remove-choice]');
        if (! removeButton) return;
        if (rows().length <= 2) return;
        removeButton.closest('[data-choice-row]').remove();
        syncChoiceRows();
    });

    list.addEventListener('change', function (event) {
        if (event.target.matches('.question-correct-input')) syncChoiceRows(event.target);
    });

    list.addEventListener('input', syncChoiceRows);
    questionText.addEventListener('input', function () {
        previewText.textContent = questionText.value.trim() || 'Question text preview';
    });

    if (modeToggle) {
        modeToggle.addEventListener('change', function () {
            if (! modeToggle.checked) {
                const checkedRows = rows().filter(function (row) {
                    return row.querySelector('.question-correct-input').checked;
                });
                checkedRows.slice(1).forEach(function (row) {
                    row.querySelector('.question-correct-input').checked = false;
                });
            }
            syncChoiceRows();
        });
    }

    syncChoiceRows();
});
</script>
@endpush





