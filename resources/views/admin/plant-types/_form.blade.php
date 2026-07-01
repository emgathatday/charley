@php
    $plantType ??= null;
@endphp

<div class="card-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Name</label>
            <input
                type="text"
                class="form-control @error('name') is-invalid @enderror"
                id="name"
                name="name"
                value="{{ old('name', $plantType?->name) }}"
                placeholder="Ammonia"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="slug" class="form-label">Slug</label>
            <input
                type="text"
                class="form-control @error('slug') is-invalid @enderror"
                id="slug"
                name="slug"
                value="{{ old('slug', $plantType?->slug) }}"
                placeholder="ammonia-plant"
                required
            >
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label for="description" class="form-label">Description</label>
            <textarea
                class="form-control @error('description') is-invalid @enderror"
                id="description"
                name="description"
                rows="4"
                placeholder="Short overview of this plant category."
            >{{ old('description', $plantType?->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="is_active" class="form-label">Status</label>
            <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active" required>
                <option value="1" @selected((string) old('is_active', (int) ($plantType?->is_active ?? true)) === '1')>Active</option>
                <option value="0" @selected((string) old('is_active', (int) ($plantType?->is_active ?? true)) === '0')>Inactive</option>
            </select>
            @error('is_active')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="sort_order" class="form-label">Sort Order</label>
            <input
                type="number"
                class="form-control @error('sort_order') is-invalid @enderror"
                id="sort_order"
                name="sort_order"
                value="{{ old('sort_order', $plantType?->sort_order ?? 0) }}"
                min="0"
                required
            >
            @error('sort_order')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
