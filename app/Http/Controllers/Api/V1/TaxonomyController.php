<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaxonomyAdminRequest;
use App\Http\Requests\TaxonomyIndexRequest;
use App\Http\Requests\TaxonomyStoreRequest;
use App\Http\Requests\TaxonomySyncRequest;
use App\Http\Requests\TaxonomyUpdateRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\Taxonomy\TaxonomyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaxonomyController extends Controller
{
    public function __construct(private readonly TaxonomyService $taxonomy)
    {
    }

    public function index(TaxonomyIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        return TagResource::collection(
            $this->taxonomy->list($data, (int) ($data['per_page'] ?? 20)),
        );
    }

    public function search(TaxonomyIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        return TagResource::collection(
            $this->taxonomy->search(
                $data['search'] ?? null,
                $data['category'] ?? null,
                (int) ($data['limit'] ?? 20),
            ),
        );
    }

    public function store(TaxonomyStoreRequest $request): JsonResponse
    {
        return (new TagResource($this->taxonomy->create($request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }

    public function update(TaxonomyUpdateRequest $request, Tag $tag): TagResource
    {
        return new TagResource($this->taxonomy->update($tag, $request->validated()));
    }

    public function destroy(TaxonomyAdminRequest $request, Tag $tag): Response
    {
        $this->taxonomy->delete($tag);

        return response()->noContent();
    }

    public function sync(TaxonomySyncRequest $request): JsonResponse
    {
        $data = $request->validated();
        $model = $this->resolveTaggable($data['taggable_type'], (int) $data['taggable_id']);
        $tagIds = $data['tag_ids'] ?? [];

        match ($data['mode'] ?? 'sync') {
            'attach' => $this->taxonomy->attach($model, $tagIds),
            'detach' => $this->taxonomy->detach($model, $tagIds),
            default => $this->taxonomy->sync($model, $tagIds),
        };

        return response()->json([
            'data' => [
                'taggable_type' => $data['taggable_type'],
                'taggable_id' => $model->getKey(),
                'mode' => $data['mode'] ?? 'sync',
                'tags' => TagResource::collection($model->tags()->orderBy('name')->get())->resolve(),
            ],
        ]);
    }

    private function resolveTaggable(string $type, int $id): Model
    {
        abort_unless(class_exists($type) && is_subclass_of($type, Model::class), Response::HTTP_UNPROCESSABLE_ENTITY);

        $model = $type::query()->findOrFail($id);

        abort_unless(method_exists($model, 'tags'), Response::HTTP_UNPROCESSABLE_ENTITY);

        return $model;
    }
}
