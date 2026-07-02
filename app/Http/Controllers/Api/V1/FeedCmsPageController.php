<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedCmsAdminRequest;
use App\Http\Requests\FeedPageIndexRequest;
use App\Http\Requests\FeedPageStoreRequest;
use App\Http\Requests\FeedPageUpdateRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\FeedCms\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FeedCmsPageController extends Controller
{
    public function __construct(private readonly PageService $pages)
    {
    }

    public function publicIndex(FeedPageIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        return PageResource::collection(
            Page::query()
                ->published()
                ->when($data['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
                ->orderByDesc('published_at')
                ->paginate((int) ($data['per_page'] ?? 20)),
        );
    }

    public function publicShow(string $slug): PageResource
    {
        return new PageResource(Page::query()->published()->slug($slug)->firstOrFail());
    }

    public function index(FeedPageIndexRequest $request): AnonymousResourceCollection
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $data = $request->validated();

        return PageResource::collection(
            Page::query()
                ->when($data['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($data['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
                ->orderByDesc('updated_at')
                ->paginate((int) ($data['per_page'] ?? 20)),
        );
    }

    public function store(FeedPageStoreRequest $request): JsonResponse
    {
        return (new PageResource($this->pages->create($request->validated(), $request->user())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(FeedCmsAdminRequest $request, Page $page): PageResource
    {
        return new PageResource($page);
    }

    public function update(FeedPageUpdateRequest $request, Page $page): PageResource
    {
        return new PageResource($this->pages->update($page, $request->validated(), $request->user()));
    }

    public function publish(FeedCmsAdminRequest $request, Page $page): PageResource
    {
        return new PageResource($this->pages->publish($page, $request->user()));
    }

    public function archive(FeedCmsAdminRequest $request, Page $page): PageResource
    {
        return new PageResource($this->pages->archive($page, $request->user()));
    }
}
