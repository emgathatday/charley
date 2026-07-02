<div class="card-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $tag->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="slug" class="form-label">Slug</label>
            <input id="slug" type="text" name="slug" value="{{ old('slug', $tag->slug ?? '') }}" class="form-control @error('slug') is-invalid @enderror" placeholder="Generated from name when empty">
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <x-taxonomy.category-filter :categories="$categories" :selected="old('category', $tag->category ?? null)" placeholder="Uncategorized" class="@error('category') is-invalid @enderror" />
            @error('category')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Usage Count</label>
            <input type="text" value="{{ $tag->usage_count ?? 0 }}" class="form-control" disabled>
        </div>
    </div>
</div>
