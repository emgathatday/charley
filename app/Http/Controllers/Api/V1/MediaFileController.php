<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachMediaFileRequest;
use App\Http\Requests\StoreMediaFileRequest;
use App\Http\Resources\MediaFileResource;
use App\Models\MediaFile;
use App\Services\MediaUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class MediaFileController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MediaFile::query()->latest();

        if ($request->user()->role !== 'admin') {
            $query->where('uploader_id', $request->user()->id);
        }

        if ($request->boolean('orphans_only')) {
            $query->where('is_orphan', true);
        }

        if ($request->filled('file_category')) {
            $query->where('file_category', $request->string('file_category')->toString());
        }

        if ($request->filled('upload_context')) {
            $query->where('upload_context', $request->string('upload_context')->toString());
        }

        return MediaFileResource::collection($query->paginate(20));
    }

    public function store(StoreMediaFileRequest $request): MediaFileResource
    {
        $mediaFile = $this->mediaUploadService->storeUpload(
            $request->file('file'),
            $request->user(),
            $request->safe()->except('file')
        );

        return MediaFileResource::make($mediaFile);
    }

    public function show(Request $request, MediaFile $mediaFile): MediaFileResource
    {
        $this->authorizeMediaFile($request, $mediaFile);

        return MediaFileResource::make($mediaFile);
    }

    public function attach(AttachMediaFileRequest $request, MediaFile $mediaFile): MediaFileResource
    {
        $this->authorizeMediaFile($request, $mediaFile);

        $attachable = $this->resolveAttachable(
            $request->string('attachable_type')->toString(),
            (int) $request->integer('attachable_id')
        );

        return MediaFileResource::make($this->mediaUploadService->attach($mediaFile, $attachable));
    }

    public function markOrphan(Request $request, MediaFile $mediaFile): MediaFileResource
    {
        $this->authorizeMediaFile($request, $mediaFile);

        $mediaFile->fill([
            'attachable_type' => null,
            'attachable_id' => null,
            'is_orphan' => true,
        ]);
        $mediaFile->save();

        return MediaFileResource::make($mediaFile);
    }

    private function authorizeMediaFile(Request $request, MediaFile $mediaFile): void
    {
        if ($request->user()->role === 'admin' || (int) $mediaFile->uploader_id === (int) $request->user()->id) {
            return;
        }

        abort(Response::HTTP_FORBIDDEN, 'You are not allowed to access this media file.');
    }

    private function resolveAttachable(string $type, int $id): Model
    {
        $class = Relation::getMorphedModel($type) ?? $type;

        if (! is_subclass_of($class, Model::class)) {
            throw ValidationException::withMessages([
                'attachable_type' => ['The selected attachable type is invalid.'],
            ]);
        }

        $attachable = $class::query()->find($id);

        if (! $attachable instanceof Model) {
            throw ValidationException::withMessages([
                'attachable_id' => ['The selected attachable record does not exist.'],
            ]);
        }

        return $attachable;
    }
}
