@php
    $domain ??= new \App\Models\KnowledgeDomain();
    $isEdit = $domain->exists;
    $nameValue = old('name', $domain->name ?? '');
    $slugValue = old('slug', $domain->slug ?? '');
    $plantTypeValue = (string) old('plant_type_id', $domain->plant_type_id ?? '');
    $selectedPlantTypeIds = collect(old('plant_type_ids', $domain->relationLoaded('plantTypes') ? $domain->plantTypes->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)
        ->all();
    $iconValue = old('icon', $domain->icon ?? '');
    $totalQuestionValue = old('total_question_count', $domain->total_question_count ?? ($domain->quizQuestions?->count() ?? 0));
    $quizQuestionValue = old('quiz_question_count', $domain->quiz_question_count ?? 50);
    $sortValue = old('sort_order', $domain->sort_order ?? 0);
    $activeValue = (string) old('is_active', (int) ($domain->is_active ?? true));
    $descriptionValue = old('description', $domain->description ?? '');
@endphp

@if (! $isEdit)
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon blue">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-platform-settings-ai-assistant"></use></svg>
            </div>
            <div>
                <div class="form-card-title">Domain Fields</div>
                <div class="form-card-sub">Name, generated slug, and the editor-facing description.</div>
            </div>
        </div>
        <div class="form-card-body">
            <div class="row row-cols-1 row-cols-md-2 g-3">
                <div class="col">
                    <div class="field">
                        <label for="name">Name <span class="req">*</span></label>
                        <input id="name" name="name" type="text" value="{{ $nameValue }}" placeholder="Primary Reformer Operations" @class(['is-invalid' => $errors->has('name')]) required>
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label for="slug">Auto-generated slug</label>
                        <input id="slug" name="slug" type="text" value="{{ $slugValue }}" placeholder="primary-reformer-operations" @class(['is-invalid' => $errors->has('slug')])>
                        <div class="field-hint">Leave blank to generate from the domain name.</div>
                        @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="field field-full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Operational monitoring, troubleshooting and safety practices for primary reformer units." @class(['is-invalid' => $errors->has('description')])>{{ $descriptionValue }}</textarea>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon blue">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-settings"></use></svg>
            </div>
            <div>
                <div class="form-card-title">Domain Settings</div>
                <div class="form-card-sub">Presentation and quiz attempt defaults.</div>
            </div>
        </div>
        <div class="form-card-body">
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <div class="col">
                    <div class="field">
                        <label for="icon">Icon</label>
                        <input id="icon" name="icon" type="text" value="{{ $iconValue }}" placeholder="Optional icon key" @class(['is-invalid' => $errors->has('icon')])>
                        @error('icon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label for="quiz_question_count">Questions per Quiz <span class="req">*</span></label>
                        <input id="quiz_question_count" name="quiz_question_count" type="number" min="1" max="200" value="{{ $quizQuestionValue }}" @class(['is-invalid' => $errors->has('quiz_question_count')]) required>
                        @error('quiz_question_count')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label for="sort_order">Sort order</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ $sortValue }}" @class(['is-invalid' => $errors->has('sort_order')]) required>
                        @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon blue">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-plant-types"></use></svg>
            </div>
            <div>
                <div class="form-card-title">Plant Types</div>
                <div class="form-card-sub">Select every plant type that this domain applies to.</div>
            </div>
        </div>
        <div class="form-card-body">
            <div class="field field-full">
                <label>Plant Types</label>
                <div class="checkbox-chip-group" id="plantTypeChips">
                    @foreach ($plantTypes as $plantType)
                        <label class="checkbox-chip">
                            <input type="checkbox" name="plant_type_ids[]" value="{{ $plantType->id }}" @checked(in_array((string) $plantType->id, $selectedPlantTypeIds, true))>
                            <span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                            {{ $plantType->name }}
                        </label>
                    @endforeach
                </div>
                <div class="field-hint">Leave every chip unchecked to make this domain global for all plant types.</div>
                @error('plant_type_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('plant_type_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
@else
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon blue">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-platform-settings-ai-assistant"></use></svg>
            </div>
            <div>
                <div class="form-card-title">Domain Fields</div>
                <div class="form-card-sub">Name, slug, and the editor-facing description.</div>
            </div>
        </div>
        <div class="form-card-body">
            <div class="row row-cols-1 row-cols-md-2 g-3">
                <div class="col">
                    <div class="field">
                        <label for="name">Name <span class="req">*</span></label>
                        <input id="name" name="name" type="text" value="{{ $nameValue }}" placeholder="Primary Reformer Operations" @class(['is-invalid' => $errors->has('name')]) required>
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label for="slug">Slug</label>
                        <input id="slug" name="slug" type="text" value="{{ $slugValue }}" placeholder="primary-reformer-operations" @class(['is-invalid' => $errors->has('slug')])>
                        @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="field field-full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Operational monitoring, troubleshooting and safety practices for primary reformer units." @class(['is-invalid' => $errors->has('description')])>{{ $descriptionValue }}</textarea>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon blue">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-settings"></use></svg>
            </div>
            <div>
                <div class="form-card-title">Domain Settings</div>
                <div class="form-card-sub">Presentation and quiz attempt defaults.</div>
            </div>
        </div>
        <div class="form-card-body">
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <div class="col">
                    <div class="field">
                        <label for="icon">Icon</label>
                        <input id="icon" name="icon" type="text" value="{{ $iconValue }}" placeholder="Optional icon key" @class(['is-invalid' => $errors->has('icon')])>
                        @error('icon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label for="quiz_question_count">Questions per Quiz <span class="req">*</span></label>
                        <input id="quiz_question_count" name="quiz_question_count" type="number" min="1" max="200" value="{{ $quizQuestionValue }}" @class(['is-invalid' => $errors->has('quiz_question_count')]) required>
                        <div class="field-hint">Random question count for each quiz attempt.</div>
                        @error('quiz_question_count')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label for="sort_order">Sort order</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ $sortValue }}" @class(['is-invalid' => $errors->has('sort_order')]) required>
                        @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label>Total question count</label>
                        <input type="number" value="{{ $totalQuestionValue }}" readonly>
                        <div class="field-hint">Cached from quiz_questions. Usually maintained by backend.</div>
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label>Created at</label>
                        <input type="text" value="{{ optional($domain->created_at)->format('Y-m-d H:i') ?? 'Not recorded' }}" readonly>
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label>Updated at</label>
                        <input type="text" value="{{ optional($domain->updated_at)->format('Y-m-d H:i') ?? 'Not recorded' }}" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon blue">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-plant-types"></use></svg>
            </div>
            <div>
                <div class="form-card-title">Plant Types</div>
                <div class="form-card-sub">Select every plant type that this domain applies to.</div>
            </div>
        </div>
        <div class="form-card-body">
            <div class="field field-full">
                <label>Plant Types</label>
                <div class="checkbox-chip-group" id="plantTypeChipsEdit">
                    @foreach ($plantTypes as $plantType)
                        <label class="checkbox-chip">
                            <input type="checkbox" name="plant_type_ids[]" value="{{ $plantType->id }}" @checked(in_array((string) $plantType->id, $selectedPlantTypeIds, true) || ($selectedPlantTypeIds === [] && $plantTypeValue === (string) $plantType->id))>
                            <span class="checkbox-chip-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                            {{ $plantType->name }}
                        </label>
                    @endforeach
                </div>
                <div class="field-hint">Leave every chip unchecked to make this domain global for all plant types.</div>
                @error('plant_type_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('plant_type_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
@endif
