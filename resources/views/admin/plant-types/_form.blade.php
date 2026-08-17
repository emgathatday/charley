@php
    $plantType ??= null;
    $isEdit = $plantType !== null;
    $nameValue = old('name', $plantType?->name ?? '');
    $slugValue = old('slug', $plantType?->slug ?? '');
    $descriptionValue = old('description', $plantType?->description ?? '');
    $sortValue = old('sort_order', $plantType?->sort_order ?? 0);
    $activeValue = (string) old('is_active', (int) ($plantType?->is_active ?? true));
@endphp

<div class="form-card">
    <div class="form-card-header">
        <div class="form-card-icon blue">
            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-library-and-pfd-content-path"></use></svg>
        </div>
        <div>
            <div class="form-card-title">Core fields</div>
            <div class="form-card-sub">Source table: Plant Types.</div>
        </div>
    </div>
    <div class="form-card-body">
        <div class="row row-cols-1 row-cols-md-2 g-3">
            <div class="col">
                <div class="field">
                    <label for="name">Name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" value="{{ $nameValue }}" placeholder="Ammonia Plant" @class(['is-invalid' => $errors->has('name')]) required>
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col">
                <div class="field">
                    <label for="slug">Slug <span class="req">*</span></label>
                    <input type="text" id="slug" name="slug" value="{{ $slugValue }}" placeholder="ammonia-plant" @class(['is-invalid' => $errors->has('slug')]) required>
                    <div class="field-hint">Unique URL identifier.</div>
                    @error('slug')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col">
                <div class="field">
                    <label for="sort_order">Sort order</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ $sortValue }}" min="0" @class(['is-invalid' => $errors->has('sort_order')]) required>
                    @error('sort_order')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col">
                <div class="field">
                    <label for="is_active_switch">Status</label>
                    <div class="plant-type-switch-stack">
                        <div class="switch-row">
                            <div>
                                <div class="sw-label">Active</div>
                                <div class="sw-desc">{{ $isEdit ? 'Available for new related records.' : 'Visible in selection flows and new linked records.' }}</div>
                            </div>
                            <input type="hidden" name="is_active" value="0">
                            <label class="switch">
                                <input id="is_active_switch" type="checkbox" name="is_active" value="1" @checked($activeValue === '1')>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    @error('is_active')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            @if ($isEdit)
                <div class="col">
                    <div class="field">
                        <label>Created at</label>
                        <input type="text" value="{{ optional($plantType->created_at)->format('Y-m-d H:i') ?? 'Not recorded' }}" readonly>
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label>Updated at</label>
                        <input type="text" value="{{ optional($plantType->updated_at)->format('Y-m-d H:i') ?? 'Not recorded' }}" readonly>
                    </div>
                </div>
            @endif
            <div class="col">
                <div class="field field-full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Primary process catalog for ammonia plant library content, questions, services, partner profiles and AI chat context." @class(['is-invalid' => $errors->has('description')])>{{ $descriptionValue }}</textarea>
                    @error('description')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
