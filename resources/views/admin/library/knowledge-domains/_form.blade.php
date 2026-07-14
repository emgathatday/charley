<div class="card card-outline card-primary mb-3">
    <div class="card-header"><h3 class="card-title mb-0">Domain Fields</h3></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Name</label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $domain->name) }}" placeholder="Knowledge domain name" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="slug">Slug</label>
                <input id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $domain->slug) }}" placeholder="knowledge-domain-slug">
                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="plant_type_id">Plant Type</label>
                <select id="plant_type_id" name="plant_type_id" class="form-select @error('plant_type_id') is-invalid @enderror">
                    <option value="">General / no plant type</option>
                    @foreach($plantTypes as $plantType)
                        <option value="{{ $plantType->id }}" @selected((string) old('plant_type_id', $domain->plant_type_id) === (string) $plantType->id)>{{ $plantType->name }}</option>
                    @endforeach
                </select>
                @error('plant_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="icon">Icon</label>
                <div class="input-group"><span class="input-group-text"><i class="{{ old('icon', $domain->icon) ?: 'bi bi-diagram-3' }}"></i></span><input id="icon" name="icon" class="form-control @error('icon') is-invalid @enderror" value="{{ old('icon', $domain->icon) }}" placeholder="bi bi-lightning-charge"></div>
                @error('icon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="quiz_question_count">Questions per attempt</label>
                <input id="quiz_question_count" name="quiz_question_count" type="number" min="1" max="200" class="form-control @error('quiz_question_count') is-invalid @enderror" value="{{ old('quiz_question_count', $domain->quiz_question_count ?? 50) }}" required>
                @error('quiz_question_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="sort_order">Sort</label>
                <input id="sort_order" name="sort_order" type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $domain->sort_order ?? 0) }}" required>
                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="is_active">Status</label>
                <select id="is_active" name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                    <option value="1" @selected((string) old('is_active', (int) ($domain->is_active ?? true)) === '1')>Active</option>
                    <option value="0" @selected((string) old('is_active', (int) ($domain->is_active ?? true)) === '0')>Inactive</option>
                </select>
                @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Domain scope and quiz purpose">{{ old('description', $domain->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
