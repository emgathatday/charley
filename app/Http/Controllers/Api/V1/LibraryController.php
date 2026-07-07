<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryAccessRequest;
use App\Http\Requests\LibraryAdminRequest;
use App\Http\Requests\LibraryIndexRequest;
use App\Http\Requests\LibraryItemStoreRequest;
use App\Http\Requests\LibraryItemUpdateRequest;
use App\Http\Resources\LibraryAccessLogResource;
use App\Http\Resources\LibraryCategoryResource;
use App\Http\Resources\LibraryItemResource;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Services\Library\LibraryAccessService;
use App\Services\Library\LibraryContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LibraryController extends Controller
{
    public function __construct(
        private readonly LibraryContentService $content,
        private readonly LibraryAccessService $access,
    ) {
    }

    public function categories(): AnonymousResourceCollection
    {
        return LibraryCategoryResource::collection(
            LibraryCategory::query()
                ->withCount('items')
                ->ordered()
                ->get(),
        );
    }

    public function index(LibraryIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        return LibraryItemResource::collection(
            $this->content->listPublished($data, (int) ($data['per_page'] ?? 20)),
        );
    }

    public function show(LibraryItem $libraryItem): LibraryItemResource
    {
        abort_unless($libraryItem->status === LibraryItem::STATUS_PUBLISHED && $libraryItem->approved_at, Response::HTTP_NOT_FOUND);

        return new LibraryItemResource($libraryItem->load(['category', 'plantType', 'fileMedia']));
    }

    public function adminIndex(LibraryIndexRequest $request): AnonymousResourceCollection
    {
        abort_unless($request->user()?->role === 'admin', Response::HTTP_FORBIDDEN);
        $data = $request->validated();

        return LibraryItemResource::collection(
            LibraryItem::query()
                ->with(['category', 'plantType', 'fileMedia'])
                ->when($data['category_id'] ?? null, fn ($query, int $categoryId) => $query->where('category_id', $categoryId))
                ->when($data['plant_type_id'] ?? null, fn ($query, int $plantTypeId) => $query->where('plant_type_id', $plantTypeId))
                ->when($data['content_type'] ?? null, fn ($query, string $contentType) => $query->where('content_type', $contentType))
                ->when($data['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
                ->latest()
                ->paginate((int) ($data['per_page'] ?? 20)),
        );
    }

    public function store(LibraryItemStoreRequest $request): JsonResponse
    {
        return (new LibraryItemResource($this->content->create($request->validated(), $request->user())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(LibraryItemUpdateRequest $request, LibraryItem $libraryItem): LibraryItemResource
    {
        return new LibraryItemResource($this->content->update($libraryItem, $request->validated()));
    }

    public function approve(LibraryAdminRequest $request, LibraryItem $libraryItem): LibraryItemResource
    {
        return new LibraryItemResource($this->content->approve($libraryItem, $request->user()));
    }

    public function archive(LibraryAdminRequest $request, LibraryItem $libraryItem): LibraryItemResource
    {
        return new LibraryItemResource($this->content->archive($libraryItem));
    }

    public function accessCheck(LibraryAccessRequest $request, LibraryItem $libraryItem): JsonResponse
    {
        $data = $request->validated();
        $tier = $data['partner_tier'] ?? null;

        return response()->json([
            'data' => [
                'can_view' => $this->access->canView($libraryItem, $request->user(), $tier),
                'can_download' => $this->access->canDownload($libraryItem, $request->user(), $tier),
                'can_copy_paste' => $this->access->canCopyPaste($libraryItem, $request->user(), $tier),
                'requires_watermark' => $this->access->requiresWatermark($libraryItem, $tier),
            ],
        ]);
    }

    public function recordAccess(LibraryAccessRequest $request, LibraryItem $libraryItem): LibraryAccessLogResource
    {
        $data = $request->validated();
        $action = $data['action'] ?? 'view';
        $tier = $data['partner_tier'] ?? null;

        if ($action === 'download') {
            $this->access->assertCanDownload($libraryItem, $request->user(), $tier);
        } else {
            $this->access->assertCanView($libraryItem, $request->user(), $tier);
        }

        return new LibraryAccessLogResource(
            $this->access->recordAccess($libraryItem, $request->user(), $action, $request->ip()),
        );
    }

    public function aiTrainable(LibraryAdminRequest $request): AnonymousResourceCollection
    {
        return LibraryItemResource::collection($this->content->aiTrainableContent(100));
    }
}
