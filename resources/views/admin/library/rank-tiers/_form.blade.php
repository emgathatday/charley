<div class="card card-outline card-primary mb-3">
    <div class="card-header"><h3 class="card-title mb-0">Tier Fields</h3></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Name</label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', data_get($rankTier, 'name', '')) }}" placeholder="Rank tier name">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="slug">Slug</label>
                <input id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', data_get($rankTier, 'slug', '')) }}" placeholder="rank-tier-slug">
                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="rank_order">Rank Order</label>
                <input id="rank_order" name="rank_order" type="number" class="form-control @error('rank_order') is-invalid @enderror" value="{{ old('rank_order', data_get($rankTier, 'rank_order', 0)) }}">
                @error('rank_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="default_cap_percentage">Default Cap %</label>
                <div class="input-group">
                    <input id="default_cap_percentage" name="default_cap_percentage" type="number" step="0.01" class="form-control @error('default_cap_percentage') is-invalid @enderror" value="{{ old('default_cap_percentage', data_get($rankTier, 'default_cap_percentage', 0)) }}">
                    <span class="input-group-text">%</span>
                    @error('default_cap_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="min_years_experience">Min Experience</label>
                <div class="input-group">
                    <input id="min_years_experience" name="min_years_experience" type="number" class="form-control @error('min_years_experience') is-invalid @enderror" value="{{ old('min_years_experience', data_get($rankTier, 'min_years_experience', '')) }}">
                    <span class="input-group-text">years</span>
                    @error('min_years_experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                @php($selectedStatus = old('status', data_get($rankTier, 'status', data_get($rankTier, 'is_active', false) ? 'active' : 'draft')))
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                    @foreach(['active' => 'Active', 'draft' => 'Draft', 'deleted' => 'Deleted'] as $status => $label)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="required_quiz_count">Required Quiz Count</label>
                <input id="required_quiz_count" name="required_quiz_count" type="number" class="form-control @error('required_quiz_count') is-invalid @enderror" value="{{ old('required_quiz_count', data_get($rankTier, 'required_quiz_count', 0)) }}">
                @error('required_quiz_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="required_mandatory_quiz_count">Required Mandatory Quiz Count</label>
                <input id="required_mandatory_quiz_count" name="required_mandatory_quiz_count" type="number" class="form-control @error('required_mandatory_quiz_count') is-invalid @enderror" value="{{ old('required_mandatory_quiz_count', data_get($rankTier, 'required_mandatory_quiz_count', 0)) }}">
                @error('required_mandatory_quiz_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>