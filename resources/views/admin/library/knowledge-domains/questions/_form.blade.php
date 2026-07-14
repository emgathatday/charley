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
    $palette = ['primary', 'info', 'warning', 'secondary', 'dark'];
@endphp

<form method="POST" action="{{ $action }}" id="question-choice-form">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="difficulty_level" value="{{ old('difficulty_level', $question->difficulty_level ?? 'medium') }}">

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card card-outline {{ $isEdit ? 'card-warning' : 'card-success' }} h-100">
                <div class="card-header"><h3 class="card-title mb-0">Question Details</h3></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label" for="question_text">Question Text</label><textarea id="question_text" name="question_text" class="form-control @error('question_text') is-invalid @enderror" rows="5" required>{{ old('question_text', $question->question_text) }}</textarea>@error('question_text')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label d-block">Status</label><div class="btn-group" role="group" aria-label="Question status">@foreach($statuses as $status)<input type="radio" class="btn-check" name="status" id="status_{{ $status }}" value="{{ $status }}" autocomplete="off" @checked(old('status', $question->status ?? 'draft') === $status)><label class="btn btn-outline-{{ $status === 'active' ? 'success' : ($status === 'draft' ? 'warning' : 'secondary') }}" for="status_{{ $status }}">{{ Str::headline($status) }}</label>@endforeach</div>@error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-4"><label class="form-label" for="question_sort_order">Sort</label><input id="question_sort_order" name="sort_order" type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $question->sort_order ?? 0) }}" required>@error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-8"><label class="form-label" for="question_image_media_id">Question Image Media ID</label><input id="question_image_media_id" name="question_image_media_id" type="number" class="form-control @error('question_image_media_id') is-invalid @enderror" value="{{ old('question_image_media_id', $question->question_image_media_id) }}" placeholder="Optional media_files id">@error('question_image_media_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label" for="question_explanation">Question Explanation</label><input id="question_explanation" name="explanation" class="form-control @error('explanation') is-invalid @enderror" value="{{ old('explanation', $question->explanation) }}" placeholder="Short explanation shown after review">@error('explanation')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-outline card-info h-100">
                <div class="card-header"><h3 class="card-title mb-0">Answer Option Guide</h3></div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Correct answer</span>
                        <span class="badge text-bg-primary">Option A</span>
                        <span class="badge text-bg-info">Option B</span>
                        <span class="badge text-bg-warning">Option C</span>
                    </div>
                    <p class="text-body-secondary mb-0">Add or remove answer options as needed. The selected correct option must be one of the filled rows.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary mt-3">
        <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title mb-0">Answer Options</h3><button type="button" class="btn btn-primary btn-sm" id="add-choice"><i class="bi bi-plus-circle me-1"></i>Add Option</button></div>
        <div class="card-body">
            @error('choices')<div class="alert alert-danger">{{ $message }}</div>@enderror
            @error('correct_choice')<div class="alert alert-danger">{{ $message }}</div>@enderror
            <div class="row g-3" id="choice-list">
                @foreach($rows as $index => $choice)
                    @php($color = $palette[$index % count($palette)])
                    <div class="col-lg-6 choice-row" data-choice-row>
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge text-bg-{{ ! empty($choice['is_correct']) || (string) $correctChoice === (string) $index ? 'success' : $color }}" data-choice-badge>Option {{ $index + 1 }}</span>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check mb-0"><input class="form-check-input" type="radio" id="correct_choice_{{ $index }}" name="correct_choice" value="{{ $index }}" @checked((string) $correctChoice === (string) $index)><label class="form-check-label" for="correct_choice_{{ $index }}">Correct</label></div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-remove-choice><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                            <textarea name="choices[{{ $index }}][choice_text]" class="form-control mb-2 @error("choices.$index.choice_text") is-invalid @enderror" rows="2" placeholder="Answer choice text">{{ $choice['choice_text'] ?? '' }}</textarea>
                            <input name="choices[{{ $index }}][explanation]" class="form-control mb-2" value="{{ $choice['explanation'] ?? '' }}" placeholder="Choice explanation">
                            <input name="choices[{{ $index }}][sort_order]" type="number" min="0" class="form-control" value="{{ $choice['sort_order'] ?? $index + 1 }}" placeholder="Sort order">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('admin.dashboard.library.knowledge-domains.edit', $domain) }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn {{ $isEdit ? 'btn-warning' : 'btn-success' }}"><i class="bi bi-save me-1"></i>{{ $isEdit ? 'Update Question' : 'Create Question' }}</button></div>
    </div>
</form>

<template id="choice-template">
    <div class="col-lg-6 choice-row" data-choice-row>
        <div class="border rounded p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge text-bg-primary" data-choice-badge>Option</span>
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check mb-0"><input class="form-check-input" type="radio" name="correct_choice"><label class="form-check-label">Correct</label></div>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-remove-choice><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <textarea class="form-control mb-2" rows="2" placeholder="Answer choice text"></textarea>
            <input class="form-control mb-2" placeholder="Choice explanation">
            <input type="number" min="0" class="form-control" placeholder="Sort order">
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('choice-list');
    const template = document.getElementById('choice-template');
    const addButton = document.getElementById('add-choice');
    const colors = ['primary', 'info', 'warning', 'secondary', 'dark'];

    function refreshChoices() {
        list.querySelectorAll('[data-choice-row]').forEach((row, index) => {
            const badge = row.querySelector('[data-choice-badge]');
            const radio = row.querySelector('input[type="radio"]');
            const label = row.querySelector('.form-check-label');
            const fields = row.querySelectorAll('textarea, input:not([type="radio"])');
            const color = radio.checked ? 'success' : colors[index % colors.length];

            badge.className = `badge text-bg-${color}`;
            badge.textContent = `Option ${index + 1}`;
            radio.id = `correct_choice_${index}`;
            radio.value = index;
            label.setAttribute('for', radio.id);
            fields[0].name = `choices[${index}][choice_text]`;
            fields[1].name = `choices[${index}][explanation]`;
            fields[2].name = `choices[${index}][sort_order]`;
            if (! fields[2].value) fields[2].value = index + 1;
        });
    }

    addButton.addEventListener('click', () => {
        list.appendChild(template.content.firstElementChild.cloneNode(true));
        refreshChoices();
    });

    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-choice]');
        if (! removeButton) return;
        if (list.querySelectorAll('[data-choice-row]').length <= 2) return;
        removeButton.closest('[data-choice-row]').remove();
        refreshChoices();
    });

    list.addEventListener('change', (event) => {
        if (event.target.matches('input[type="radio"]')) refreshChoices();
    });

    refreshChoices();
});
</script>
