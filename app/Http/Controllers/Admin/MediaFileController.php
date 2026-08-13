<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaFileRequest;
use App\Models\MediaFile;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaFileController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
    ) {
    }

    public function index(Request $request, ?MediaFile $mediaFile = null): View
    {
        $query = MediaFile::query()->latest();

        if ($request->filled('file_category')) {
            $query->where('file_category', $request->string('file_category')->toString());
        }

        if ($request->filled('processing_status')) {
            $query->where('processing_status', $request->string('processing_status')->toString());
        }

        if ($request->filled('upload_context')) {
            $query->where('upload_context', $request->string('upload_context')->toString());
        }

        if ($request->boolean('orphans_only')) {
            $query->where('is_orphan', true);
        }

        $mediaFiles = $query->paginate(15)->withQueryString();

        return view('admin.media-files.index', [
            'mediaFiles' => $mediaFiles,
            'selectedMediaFile' => $mediaFile ?? $mediaFiles->first(),
            'totalUploads' => MediaFile::query()->count(),
            'attachedFiles' => MediaFile::query()->whereNotNull('attachable_type')->whereNotNull('attachable_id')->count(),
            'librarySources' => MediaFile::query()->where('upload_context', 'library_item')->count(),
            'quizImages' => MediaFile::query()->whereIn('upload_context', ['question_attachment', 'answer_attachment'])->count(),
            'storageUsedBytes' => (int) MediaFile::query()->sum('size'),
            'orphanFiles' => MediaFile::query()->where('is_orphan', true)->count(),
            'processingFiles' => MediaFile::query()->where('processing_status', 'processing')->count(),
        ]);
    }

    public function store(StoreMediaFileRequest $request): RedirectResponse
    {
        $this->mediaUploadService->storeUpload(
            $request->file('file'),
            $request->user(),
            $request->safe()->except('file')
        );

        return redirect()
            ->route('admin.dashboard.media-files.index')
            ->with('status', 'Media file uploaded.');
    }

    public function show(Request $request, MediaFile $mediaFile): View
    {
        return $this->index($request, $mediaFile);
    }
}
