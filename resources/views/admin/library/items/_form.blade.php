<div class="row g-3">
    <div class="col-lg-8">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Content Details</h3></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8"><label class="form-label" for="title">Title</label><input id="title" class="form-control" value="{{ $item['title'] }}" placeholder="Library item title"></div>
                    <div class="col-md-4"><label class="form-label" for="slug">Slug</label><input id="slug" class="form-control" value="{{ $item['slug'] }}" placeholder="auto-generated-slug"></div>
                    <div class="col-md-4"><label class="form-label" for="category">Category</label><select id="category" class="form-select">@foreach($categories as $category)<option @selected($item['category'] === $category)>{{ $category }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label" for="plant_type">Plant Type</label><select id="plant_type" class="form-select">@foreach($plantTypes as $plantType)<option @selected($item['plant_type'] === $plantType)>{{ $plantType }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label" for="content_type">Content Type</label><select id="content_type" class="form-select">@foreach($contentTypes as $contentType)<option @selected($item['content_type'] === $contentType)>{{ Str::headline($contentType) }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label" for="author">Author</label><input id="author" class="form-control" value="{{ $item['author'] }}" placeholder="Author or team"></div>
                    <div class="col-md-6"><label class="form-label" for="source">Source</label><input id="source" class="form-control" value="{{ $item['source'] }}" placeholder="Source reference"></div>
                    <div class="col-md-3"><label class="form-label" for="year">Year</label><input id="year" type="number" class="form-control" value="{{ $item['year'] }}"></div>
                    <div class="col-md-3"><label class="form-label" for="published_year">Published Year</label><input id="published_year" type="number" class="form-control" value="{{ $item['published_year'] }}"></div>
                    <div class="col-md-6"><label class="form-label" for="file_media_id">File Media Reference</label><div class="input-group"><span class="input-group-text"><i class="bi bi-paperclip"></i></span><input id="file_media_id" class="form-control" value="{{ $item['file_media_id'] }}" placeholder="Media file id"></div><div class="form-text">{{ $item['file_label'] }}</div></div>
                    <div class="col-12"><label class="form-label" for="summary">Summary</label><textarea id="summary" class="form-control" rows="3">{{ $item['summary'] }}</textarea></div>
                    <div class="col-12"><label class="form-label" for="content">AI/RAG Content</label><textarea id="content" class="form-control" rows="5">{{ $item['content'] }}</textarea></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-info mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Review State</h3></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label" for="status">Publication Status</label><select id="status" class="form-select">@foreach($statuses as $status)<option @selected($item['status'] === $status)>{{ Str::headline($status) }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label" for="access_level">Access Level</label><select id="access_level" class="form-select">@foreach($accessLevels as $accessLevel)<option @selected($item['access_level'] === $accessLevel)>{{ Str::headline($accessLevel) }}</option>@endforeach</select></div>
                <div class="d-flex flex-column gap-2">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="download_allowed" @checked($item['download_allowed'])><label class="form-check-label" for="download_allowed">Download allowed</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="copy_paste_disabled" @checked($item['copy_paste_disabled'])><label class="form-check-label" for="copy_paste_disabled">Copy/paste disabled</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="is_ai_trainable" @checked($item['is_ai_trainable'])><label class="form-check-label" for="is_ai_trainable">AI trainable</label></div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title mb-0">Approval Snapshot</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Status</dt><dd class="col-7"><span class="badge text-bg-{{ $item['approval_status'] === 'Approved' ? 'success' : 'warning' }}">{{ $item['approval_status'] }}</span></dd>
                    <dt class="col-5">Approved by</dt><dd class="col-7">{{ $item['approved_by'] ?? 'Not assigned' }}</dd>
                    <dt class="col-5">Approved at</dt><dd class="col-7">{{ $item['approved_at'] ?? 'Pending' }}</dd>
                    <dt class="col-5">Views</dt><dd class="col-7">{{ number_format($item['view_count']) }}</dd>
                    <dt class="col-5">Downloads</dt><dd class="col-7">{{ number_format($item['download_count']) }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>