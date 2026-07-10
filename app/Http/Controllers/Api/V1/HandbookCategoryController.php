<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Handbook\HandbookCategoryIndexRequest;
use App\Http\Resources\HandbookCategoryResource;
use App\Http\Resources\HandbookCategoryTreeResource;
use App\Http\Resources\HandbookHotspotResource;
use App\Models\HandbookCategory;
use App\Services\HandbookService;

class HandbookCategoryController extends Controller
{
    public function __construct(private readonly HandbookService $handbook)
    {
    }

    public function index(HandbookCategoryIndexRequest $request)
    {
        $categories = HandbookCategory::query()
            ->with(['plantType', 'layoutImage', 'children'])
            ->withCount('articles')
            ->when($request->integer('plant_type_id'), fn ($query, int $plantTypeId) => $query->where('plant_type_id', $plantTypeId))
            ->when($request->boolean('published_only', true), fn ($query) => $query->published())
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate($request->integer('per_page') ?: 15);

        return HandbookCategoryResource::collection($categories);
    }

    public function tree(HandbookCategoryIndexRequest $request)
    {
        return HandbookCategoryTreeResource::collection($this->handbook->categoryTree(
            $request->integer('plant_type_id') ?: null,
            $request->boolean('published_only', true),
        ));
    }

    public function hotspots(HandbookCategoryIndexRequest $request)
    {
        return HandbookHotspotResource::collection($this->handbook->plantLayoutHotspots(
            $request->integer('plant_type_id') ?: null,
            $request->boolean('published_only', true),
        ));
    }

    public function show(HandbookCategory $handbookCategory): HandbookCategoryResource
    {
        return new HandbookCategoryResource(
            $handbookCategory->load(['plantType', 'layoutImage', 'children', 'articles']),
        );
    }
}
