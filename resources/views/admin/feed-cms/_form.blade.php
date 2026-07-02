@php
    $contentBlocks = old('content_blocks_json', json_encode($page->content_blocks ?? [['type' => 'paragraph', 'content' => '']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $seoMeta = old('seo_meta_json', json_encode($page->seo_meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
@endphp

@csrf
@isset($method)
    @method($method)
@endisset

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title mb-0">Page Content</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input id="title" type="text" name="title" value="{{ old('title', $page->title) }}" class="form-control @error('title') is-invalid @enderror" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input id="slug" type="text" name="slug" value="{{ old('slug', $page->slug) }}" class="form-control @error('slug') is-invalid @enderror" placeholder="auto-generated-from-title">
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="content_blocks_json" class="form-label">Content Blocks JSON</label>
                    <textarea id="content_blocks_json" name="content_blocks_json" rows="14" class="form-control font-monospace @error('content_blocks_json') is-invalid @enderror" required>{{ $contentBlocks }}</textarea>
                    @error('content_blocks_json')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="seo_meta_json" class="form-label">SEO Meta JSON</label>
                    <textarea id="seo_meta_json" name="seo_meta_json" rows="5" class="form-control font-monospace @error('seo_meta_json') is-invalid @enderror">{{ $seoMeta }}</textarea>
                    @error('seo_meta_json')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title mb-0">Publishing</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $page->status) === $status)>{{ Str::headline($status) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="published_at" class="form-label">Published At</label>
                    <input id="published_at" type="datetime-local" name="published_at" value="{{ old('published_at', optional($page->published_at)->format('Y-m-d\TH:i')) }}" class="form-control @error('published_at') is-invalid @enderror">
                    @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-check form-switch mb-3">
                    <input id="is_system_page" type="checkbox" name="is_system_page" value="1" class="form-check-input" @checked(old('is_system_page', $page->is_system_page))>
                    <label for="is_system_page" class="form-check-label">System Page</label>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Page</button>
                    <a href="{{ route('admin.dashboard.feed-cms.index') }}" class="btn btn-outline-secondary">Back to Feed CMS</a>
                </div>
            </div>
        </div>
    </div>
</div>
