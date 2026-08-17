@extends('layouts.rebuild-dashboard')

@section('title', 'Media Files')

@section('content')
    @php
        $fileCategories = ['image', 'document', 'process_diagram', 'video', 'presentation', 'audio', 'archive', 'other'];
        $uploadContexts = ['profile_photo', 'verification_document', 'library_item', 'event_thumbnail', 'post_attachment', 'question_attachment', 'answer_attachment', 'partner_asset', 'service_asset', 'general'];
        $processingStatuses = ['pending', 'processing', 'processed', 'failed'];
        $formatLabel = fn (?string $value): string => $value ? Str::headline(str_replace('_', ' ', $value)) : 'None';
        $formatBytes = function (int|float|null $bytes): string {
            $bytes = (float) ($bytes ?? 0);
            if ($bytes <= 0) {
                return '0 B';
            }
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $index = 0;
            while ($bytes >= 1024 && $index < count($units) - 1) {
                $bytes /= 1024;
                $index++;
            }
            return ($index === 0 ? number_format($bytes, 0) : number_format($bytes, 1)).' '.$units[$index];
        };
        $mediaType = function ($mediaFile): array {
            $category = $mediaFile->file_category;
            $mime = (string) $mediaFile->mime_type;
            $extension = strtoupper(pathinfo((string) $mediaFile->original_name, PATHINFO_EXTENSION));

            if ($category === 'image' || str_starts_with($mime, 'image/')) {
                return [$extension ?: 'IMG', 'image', 'icon-settings-2'];
            }
            if ($category === 'video' || str_starts_with($mime, 'video/')) {
                return [$extension ?: 'VID', 'video', 'icon-quiz-and-question-bank'];
            }
            if ($category === 'presentation' || in_array($extension, ['PPT', 'PPTX'], true)) {
                return [$extension ?: 'DECK', 'deck', 'icon-library'];
            }
            if ($extension === 'PDF' || str_contains($mime, 'pdf')) {
                return ['PDF', 'pdf', 'icon-library'];
            }

            return [$extension ?: strtoupper((string) ($category ?: 'FILE')), 'pdf', 'icon-library'];
        };
        $sourceLabel = function ($mediaFile) use ($formatLabel): string {
            return match ($mediaFile->upload_context) {
                'library_item' => 'Charley Library',
                'question_attachment', 'answer_attachment' => 'Q&A Upload',
                'partner_asset' => 'Partner Upload',
                'general' => 'General Upload',
                default => $formatLabel($mediaFile->upload_context),
            };
        };
        $pillar = function ($mediaFile): array {
            return match ($mediaFile->upload_context) {
                'question_attachment', 'answer_attachment' => ['Q&A', 'qa'],
                'profile_photo', 'verification_document', 'partner_asset', 'service_asset' => ['Partner', 'library'],
                default => ['Library', 'library'],
            };
        };
        $status = function ($mediaFile): array {
            if ($mediaFile->is_orphan) {
                return ['Orphan', 'knowledge-badge-warning'];
            }
            return match ($mediaFile->processing_status) {
                'processed' => ['Processed', 'knowledge-badge-active'],
                'processing' => ['Processing', 'knowledge-badge-info'],
                'failed' => ['Failed', 'knowledge-badge-muted'],
                'pending' => ['Pending', 'knowledge-badge-warning'],
                default => ['Stored', 'knowledge-badge-active'],
            };
        };
        $showingFrom = $mediaFiles->total() ? $mediaFiles->firstItem() : 0;
        $showingTo = $mediaFiles->total() ? $mediaFiles->lastItem() : 0;
        $mediaStatCards = [
            ['label' => 'Total Files', 'value' => number_format($totalUploads), 'sub' => 'media_files'],
            ['label' => 'Library Sources', 'value' => number_format($librarySources ?? 0), 'sub' => 'Library item links'],
            ['label' => 'Quiz Images', 'value' => number_format($quizImages ?? 0), 'sub' => 'Question media refs'],
            ['label' => 'Storage Used', 'value' => $formatBytes($storageUsedBytes ?? 0), 'sub' => 'Stored upload size'],
        ];
    @endphp

    @include('templates.components.alert-session')

    <div class="page-head">
        <div>
            <div class="page-title">Media Files</div>
            <div class="page-subtitle">Manage uploaded files referenced by library_items, quiz questions, AI dataset sources and partner content.</div>
        </div>
        <div class="page-head-actions">
            <button class="btn btn-outline" type="button">Sync metadata</button>
            <button class="btn btn-primary" type="button" id="mediaUploadShortcut"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-2"></use></svg>Upload media</button>
        </div>
    </div>

    <x-admin.stat-cards :items="$mediaStatCards" row-class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 media-stat-row" />

    <div class="row g-3 align-items-center mb-3">
        <div class="col-12 col-xl">
            @php
                $mediaTabBar = [
                    'tabs' => [
                        ['label' => 'All Files', 'count' => $totalUploads, 'active' => ! request()->filled('upload_context'), 'href' => route('admin.dashboard.media-files.index')],
                        ['label' => 'Library', 'count' => $librarySources ?? 0, 'active' => request('upload_context') === 'library_item', 'href' => route('admin.dashboard.media-files.index', ['upload_context' => 'library_item'])],
                        ['type' => 'button', 'label' => 'AI Dataset', 'count' => 0],
                        ['label' => 'Q&A Uploads', 'count' => $quizImages ?? 0, 'active' => request('upload_context') === 'question_attachment', 'href' => route('admin.dashboard.media-files.index', ['upload_context' => 'question_attachment'])],
                    ],
                ];
            @endphp
            <x-admin.tab-bar :items="$mediaTabBar" />
        </div>
    </div>

    <div class="table-wrap media-files-wrap">
        <div class="table-header table-head-panel">
            <div class="table-head-main">
                <div class="table-title">Media File Listing</div>
                <div class="table-meta">Showing {{ number_format($showingFrom) }}-{{ number_format($showingTo) }} of {{ number_format($mediaFiles->total()) }} files.</div>
            </div>
            <form method="GET" action="{{ route('admin.dashboard.media-files.index') }}" class="table-head-actions media-table-actions">
                <select class="filter-select" name="file_category">
                    <option value="">All Types</option>
                    @foreach ($fileCategories as $category)
                        <option value="{{ $category }}" @selected(request('file_category') === $category)>{{ $formatLabel($category) }}</option>
                    @endforeach
                </select>
                <select class="filter-select" name="upload_context">
                    <option value="">All Sources</option>
                    @foreach ($uploadContexts as $context)
                        <option value="{{ $context }}" @selected(request('upload_context') === $context)>{{ $formatLabel($context) }}</option>
                    @endforeach
                </select>
                <select class="filter-select" name="processing_status">
                    <option value="">All Statuses</option>
                    @foreach ($processingStatuses as $itemStatus)
                        <option value="{{ $itemStatus }}" @selected(request('processing_status') === $itemStatus)>{{ $formatLabel($itemStatus) }}</option>
                    @endforeach
                    <option value="" @selected(request()->boolean('orphans_only'))>Orphans shown by checkbox</option>
                </select>
                <select class="filter-select" disabled>
                    <option>All Plants</option>
                </select>
                <label class="media-orphan-filter"><input type="checkbox" name="orphans_only" value="1" @checked(request()->boolean('orphans_only'))> Orphans</label>
                <button class="btn-outline btn-filter" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-filter"></use></svg>Filter</button>
                <div class="view-toggle" aria-label="Toggle media view">
                    <button class="view-btn active" type="button" id="mediaListBtn" aria-label="List view" onclick="setMediaView('list')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-list"></use></svg></button>
                    <button class="view-btn" type="button" id="mediaGridBtn" aria-label="Grid view" onclick="setMediaView('grid')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-view-grid"></use></svg></button>
                </div>
            </form>
        </div>

        <div id="mediaViewWrapper">
            <div class="media-list-view" data-media-view="list">
                <div class="table-scroll">
                    <table class="media-files-table">
                        <thead>
                            <tr>
                                <th class="media-check-col"><input class="media-check-input" type="checkbox" aria-label="Select all media files"></th>
                                <th>File</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Source</th>
                                <th>Plant</th>
                                <th>Pillar</th>
                                <th>Status</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mediaFiles as $mediaFile)
                                @php
                                    [$typeLabel, $typeClass, $iconName] = $mediaType($mediaFile);
                                    [$pillarLabel, $pillarClass] = $pillar($mediaFile);
                                    [$statusLabel, $statusClass] = $status($mediaFile);
                                    $selected = $selectedMediaFile && (int) $selectedMediaFile->id === (int) $mediaFile->id;
                                @endphp
                                <tr @class(['selected' => $selected])>
                                    <td><input class="media-check-input" type="checkbox" aria-label="Select media file #{{ $mediaFile->id }}"></td>
                                    <td>
                                        <div class="media-file-cell">
                                            <div class="media-file-icon media-file-{{ $typeClass }}"><svg class="icon"><use href="/assets/icons/sprite.svg#{{ $iconName }}"></use></svg></div>
                                            <div><strong>{{ $mediaFile->original_name }}</strong><span>media_files #{{ $mediaFile->id }} - {{ $mediaFile->mime_type ?: 'No MIME type' }}</span></div>
                                        </div>
                                    </td>
                                    <td><span class="media-type-badge media-type-{{ $typeClass }}">{{ $typeLabel }}</span></td>
                                    <td>{{ $formatBytes($mediaFile->size) }}</td>
                                    <td><span class="media-source-pill">{{ $sourceLabel($mediaFile) }}</span></td>
                                    <td>General</td>
                                    <td><span class="media-pillar media-pillar-{{ $pillarClass }}">{{ $pillarLabel }}</span></td>
                                    <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td>{{ optional($mediaFile->created_at)->format('d M Y') ?? 'Not recorded' }}</td>
                                    <td>
                                        <div class="action-cell">
                                            <a href="{{ route('admin.dashboard.media-files.show', $mediaFile) }}" class="act-btn primary" aria-label="View file"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-views"></use></svg></a>
                                            <a href="#" class="act-btn" aria-label="Edit file" onclick="event.preventDefault();"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-plus"></use></svg></a>
                                            <button class="act-btn danger" type="button" aria-label="Delete file"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-admin-actions"></use></svg></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted py-4">No media files found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="media-grid-view" data-media-view="grid" hidden>
                <div class="media-grid">
                    @forelse ($mediaFiles as $mediaFile)
                        @php
                            [$typeLabel, $typeClass, $iconName] = $mediaType($mediaFile);
                            $selected = $selectedMediaFile && (int) $selectedMediaFile->id === (int) $mediaFile->id;
                        @endphp
                        <div @class(['media-card', 'selected' => $selected]) role="button" tabindex="0" onclick="toggleMediaCard(this)">
                            <span class="media-card-check"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-2"></use></svg></span>
                            <div class="media-card-actions"><div class="action-cell media-card-action-cell"><a href="{{ route('admin.dashboard.media-files.show', $mediaFile) }}" class="action-btn primary" aria-label="View file" onclick="event.stopPropagation();"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-views"></use></svg></a><a href="#" class="action-btn" aria-label="Edit file" onclick="event.preventDefault(); event.stopPropagation();"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-plus"></use></svg></a><button class="action-btn danger" type="button" aria-label="Delete file" onclick="event.stopPropagation();"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-admin-actions"></use></svg></button></div></div>
                            <div class="media-card-thumb media-file-{{ $typeClass }}"><svg class="icon"><use href="/assets/icons/sprite.svg#{{ $iconName }}"></use></svg><span>{{ $typeLabel }}</span></div>
                            <div class="media-card-body"><strong>{{ $mediaFile->original_name }}</strong><div><span>{{ $formatBytes($mediaFile->size) }}</span><span>{{ $formatLabel($mediaFile->file_category) }}</span></div><small>{{ $sourceLabel($mediaFile) }}</small></div>
                        </div>
                    @empty
                        <div class="media-card"><div class="media-card-body"><strong>No media files found.</strong><small>Upload media to populate this registry.</small></div></div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="pagination media-files-pagination" id="mediaPagination">
            @if ($mediaFiles->total())
                <span class="page-info">Showing {{ number_format($showingFrom) }}-{{ number_format($showingTo) }} of {{ number_format($mediaFiles->total()) }} files</span>
            @endif
            {{ $mediaFiles->links() }}
        </div>
    </div>

    <div class="detail-grid media-files-detail-grid mt-3">
        <div class="col-main">
            <div class="card card-padded" id="mediaUploadPanel">
                <div class="verification-detail-head">
                    <div class="card-title"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-2"></use></svg>Upload media</div>
                    <span class="card-title-count">StoreMediaFileRequest</span>
                </div>
                <form method="post" action="{{ route('admin.dashboard.media-files.store') }}" enctype="multipart/form-data" class="row row-cols-1 row-cols-md-2 g-3">
                    @csrf
                    <div class="col">
                        <div class="field"><label for="media-file">File <span class="req">*</span></label><input type="file" id="media-file" name="file" @class(['is-invalid' => $errors->has('file')])>@error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    </div>
                    <div class="col">
                        <div class="field"><label for="file-category">File category</label><select id="file-category" name="file_category"> <option value="">Select category</option>@foreach ($fileCategories as $category)<option value="{{ $category }}" @selected(old('file_category') === $category)>{{ $formatLabel($category) }}</option>@endforeach</select></div>
                    </div>
                    <div class="col">
                        <div class="field"><label for="upload-context">Upload context</label><select id="upload-context" name="upload_context"><option value="">Select context</option>@foreach ($uploadContexts as $context)<option value="{{ $context }}" @selected(old('upload_context') === $context)>{{ $formatLabel($context) }}</option>@endforeach</select></div>
                    </div>
                    <div class="col">
                        <div class="field"><label for="disk">Disk</label><input type="text" id="disk" name="disk" value="{{ old('disk', 's3') }}"></div>
                    </div>
                    <div class="col">
                        <div class="field"><label for="directory">Directory</label><input type="text" id="directory" name="directory" value="{{ old('directory') }}" placeholder="Optional storage directory"></div>
                    </div>
                    <div class="col">
                        <div class="field"><label for="sort_order">Sort order</label><input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"></div>
                    </div>
                    <div class="col">
                        <label class="media-orphan-filter"><input type="checkbox" name="is_orphan" value="1" @checked(old('is_orphan'))> Mark as orphan after upload</label>
                    </div>
                    <div class="col"><button type="submit" class="btn btn-primary">Upload media</button></div>
                </form>
            </div>
        </div>

        <div class="col-side">
            <div class="side-card">
                <div class="card card-padded">
                    <div class="card-title">Selected detail</div>
                    @if ($selectedMediaFile)
                        <div class="knowledge-schema-list"><span>ID #{{ $selectedMediaFile->id }}</span><span>{{ $formatLabel($selectedMediaFile->file_category) }}</span><span>{{ $status($selectedMediaFile)[0] }}</span></div>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Path</dt><dd class="col-sm-7 text-break">{{ $selectedMediaFile->path }}</dd>
                            <dt class="col-sm-5">MIME type</dt><dd class="col-sm-7">{{ $selectedMediaFile->mime_type }}</dd>
                            <dt class="col-sm-5">Size</dt><dd class="col-sm-7">{{ $formatBytes($selectedMediaFile->size) }}</dd>
                            <dt class="col-sm-5">Watermark</dt><dd class="col-sm-7">{{ $selectedMediaFile->is_watermarked ? 'Yes' : 'No' }}</dd>
                            <dt class="col-sm-5">Orphan</dt><dd class="col-sm-7">{{ $selectedMediaFile->is_orphan ? 'Yes' : 'No' }}</dd>
                            <dt class="col-sm-5">Attached to</dt><dd class="col-sm-7">{{ $selectedMediaFile->attachable_type ? class_basename($selectedMediaFile->attachable_type).' #'.$selectedMediaFile->attachable_id : 'None' }}</dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">No media file selected.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function setMediaView(view) {
        const list = document.querySelector('[data-media-view="list"]');
        const grid = document.querySelector('[data-media-view="grid"]');
        const listBtn = document.getElementById('mediaListBtn');
        const gridBtn = document.getElementById('mediaGridBtn');

        sessionStorage.setItem('charley.mediaFiles.view', view);
        if (list) list.hidden = view !== 'list';
        if (grid) grid.hidden = view !== 'grid';
        if (listBtn) listBtn.classList.toggle('active', view === 'list');
        if (gridBtn) gridBtn.classList.toggle('active', view === 'grid');
    }

    function toggleMediaCard(card) {
        card.classList.toggle('selected');
    }

    document.addEventListener('DOMContentLoaded', function () {
        setMediaView(sessionStorage.getItem('charley.mediaFiles.view') || 'list');
        const uploadShortcut = document.getElementById('mediaUploadShortcut');
        const uploadPanel = document.getElementById('mediaUploadPanel');
        if (uploadShortcut && uploadPanel) {
            uploadShortcut.addEventListener('click', function () {
                uploadPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                const fileInput = document.getElementById('media-file');
                if (fileInput) fileInput.focus();
            });
        }
    });
</script>
@endpush