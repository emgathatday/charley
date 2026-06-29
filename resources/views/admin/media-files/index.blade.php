@extends('layouts.master')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Media Files</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Media Files</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $totalUploads }}</h3>
                            <p>Total uploads</p>
                        </div>
                        <div class="icon"><i class="fas fa-photo-video"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $attachedFiles }}</h3>
                            <p>Attached files</p>
                        </div>
                        <div class="icon"><i class="fas fa-link"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $orphanFiles }}</h3>
                            <p>Orphan files</p>
                        </div>
                        <div class="icon"><i class="fas fa-unlink"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>{{ $processingFiles }}</h3>
                            <p>Processing</p>
                        </div>
                        <div class="icon"><i class="fas fa-cog"></i></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Media registry</h3>
                        </div>
                        <div class="card-body">
                            <form method="get" action="{{ route('admin.dashboard.media-files.index') }}" class="row">
                                <div class="form-group col-md-4">
                                    <label for="file-category-filter">File category</label>
                                    <select class="form-control" id="file-category-filter" name="file_category">
                                        <option value="">All categories</option>
                                        @foreach (['image', 'document', 'process_diagram', 'video', 'presentation', 'audio', 'archive', 'other'] as $category)
                                            <option value="{{ $category }}" @selected(request('file_category') === $category)>{{ str_replace('_', ' ', ucfirst($category)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="processing-status-filter">Processing status</label>
                                    <select class="form-control" id="processing-status-filter" name="processing_status">
                                        <option value="">All statuses</option>
                                        @foreach (['pending', 'processing', 'processed', 'failed'] as $status)
                                            <option value="{{ $status }}" @selected(request('processing_status') === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="orphans-only" name="orphans_only" @checked(request()->boolean('orphans_only'))>
                                        <label class="form-check-label" for="orphans-only">Orphans</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                </div>
                            </form>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Original name</th>
                                        <th>Category</th>
                                        <th>Context</th>
                                        <th>Disk</th>
                                        <th>Status</th>
                                        <th>Attached to</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($mediaFiles as $mediaFile)
                                        <tr>
                                            <td><a href="{{ route('admin.dashboard.media-files.show', $mediaFile) }}">#{{ $mediaFile->id }}</a></td>
                                            <td>{{ $mediaFile->original_name }}</td>
                                            <td>{{ $mediaFile->file_category ?? 'none' }}</td>
                                            <td>{{ $mediaFile->upload_context ?? 'none' }}</td>
                                            <td>{{ $mediaFile->disk }}</td>
                                            <td>{{ $mediaFile->processing_status ?? 'none' }}</td>
                                            <td>{{ $mediaFile->attachable_type ? class_basename($mediaFile->attachable_type).' #'.$mediaFile->attachable_id : 'orphan' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No media files found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer clearfix">
                            {{ $mediaFiles->links() }}
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Upload file</h3>
                        </div>
                        <form method="post" action="{{ route('admin.dashboard.media-files.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="media-file">File</label>
                                    <input type="file" class="form-control @error('file') is-invalid @enderror" id="media-file" name="file">
                                    @error('file')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group">
                                    <label for="file-category">File category</label>
                                    <select class="form-control" id="file-category" name="file_category">
                                        <option value="">Select category</option>
                                        @foreach (['image', 'document', 'process_diagram', 'video', 'presentation', 'audio', 'archive', 'other'] as $category)
                                            <option value="{{ $category }}">{{ str_replace('_', ' ', ucfirst($category)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="upload-context">Upload context</label>
                                    <select class="form-control" id="upload-context" name="upload_context">
                                        <option value="">Select context</option>
                                        @foreach (['profile_photo', 'verification_document', 'library_item', 'event_thumbnail', 'post_attachment', 'question_attachment', 'answer_attachment', 'partner_asset', 'service_asset', 'general'] as $context)
                                            <option value="{{ $context }}">{{ str_replace('_', ' ', ucfirst($context)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="disk">Disk</label>
                                    <input type="text" class="form-control" id="disk" name="disk" value="s3">
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Upload</button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Detail</h3>
                        </div>
                        <div class="card-body">
                            @if ($selectedMediaFile)
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">Path</dt>
                                    <dd class="col-sm-7 text-break">{{ $selectedMediaFile->path }}</dd>
                                    <dt class="col-sm-5">MIME type</dt>
                                    <dd class="col-sm-7">{{ $selectedMediaFile->mime_type }}</dd>
                                    <dt class="col-sm-5">Size</dt>
                                    <dd class="col-sm-7">{{ number_format($selectedMediaFile->size) }} bytes</dd>
                                    <dt class="col-sm-5">Watermark</dt>
                                    <dd class="col-sm-7">{{ $selectedMediaFile->is_watermarked ? 'Yes' : 'No' }}</dd>
                                    <dt class="col-sm-5">Orphan</dt>
                                    <dd class="col-sm-7">{{ $selectedMediaFile->is_orphan ? 'Yes' : 'No' }}</dd>
                                </dl>
                            @else
                                <p class="text-muted mb-0">No media file selected.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
