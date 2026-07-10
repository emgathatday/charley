<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Handbook\HandbookArticleIndexRequest;
use App\Http\Requests\Handbook\HandbookArticlePublishRequest;
use App\Http\Requests\Handbook\HandbookRelatedItemRequest;
use App\Http\Resources\HandbookArticleResource;
use App\Http\Resources\HandbookMetadataResource;
use App\Http\Resources\HandbookRelatedItemResource;
use App\Models\HandbookArticle;
use App\Services\HandbookService;

class HandbookArticleController extends Controller
{
    public function __construct(private readonly HandbookService $handbook)
    {
    }

    public function index(HandbookArticleIndexRequest $request)
    {
        $filters = $request->safe()->except('per_page');

        return HandbookArticleResource::collection(
            $this->handbook->searchArticles($filters, $request->integer('per_page') ?: 15),
        );
    }

    public function show(HandbookArticle $handbookArticle): HandbookArticleResource
    {
        return new HandbookArticleResource(
            $handbookArticle->load(['category.plantType', 'metadata', 'relatedItems.relatable']),
        );
    }

    public function metadata(HandbookArticle $handbookArticle)
    {
        return HandbookMetadataResource::collection(
            $this->handbook->metadataGrouped($handbookArticle)->flatten(1),
        );
    }

    public function relatedItems(HandbookArticle $handbookArticle)
    {
        return HandbookRelatedItemResource::collection(
            $handbookArticle->relatedItems()->with('relatable')->orderBy('sort_order')->get(),
        );
    }

    public function publish(HandbookArticlePublishRequest $request, HandbookArticle $handbookArticle): HandbookArticleResource
    {
        return new HandbookArticleResource(
            $this->handbook->publishArticle($handbookArticle, $request->integer('user_id') ?: null),
        );
    }

    public function linkRelatedItem(HandbookRelatedItemRequest $request, HandbookArticle $handbookArticle): HandbookRelatedItemResource
    {
        $relatedItem = $this->handbook->linkRelatedItem(
            $handbookArticle,
            $request->string('relatable_type')->toString(),
            $request->integer('relatable_id'),
            $request->string('relation_type')->toString(),
            $request->integer('sort_order'),
        );

        return new HandbookRelatedItemResource($relatedItem->load('relatable'));
    }
}
