<?php

namespace App\Services;

use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\HandbookMetadata;
use App\Models\HandbookRelatedItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class HandbookService
{
    public function __construct(
        private readonly HandbookCategory $categories,
        private readonly HandbookArticle $articles,
        private readonly HandbookMetadata $metadata,
        private readonly HandbookRelatedItem $relatedItems,
    ) {
    }

    public function categoryTree(?int $plantTypeId = null, bool $publishedOnly = true): Collection
    {
        $categories = $this->categories->newQuery()
            ->with(['children' => fn ($query) => $query->orderBy('sort_order')->orderBy('title')])
            ->when($plantTypeId, fn (Builder $query) => $query->where('plant_type_id', $plantTypeId))
            ->when($publishedOnly, fn (Builder $query) => $query->published())
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return $categories
            ->whereNull('parent_id')
            ->values()
            ->map(fn (HandbookCategory $category) => $this->formatCategoryNode($category, $categories));
    }

    public function plantLayoutHotspots(?int $plantTypeId = null, bool $publishedOnly = true): Collection
    {
        return $this->categories->newQuery()
            ->with(['layoutImage', 'plantType'])
            ->whereNotNull('map_coordinates')
            ->when($plantTypeId, fn (Builder $query) => $query->where('plant_type_id', $plantTypeId))
            ->when($publishedOnly, fn (Builder $query) => $query->published())
            ->orderBy('sort_order')
            ->get()
            ->map(fn (HandbookCategory $category): array => [
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'plant_type_id' => $category->plant_type_id,
                'layout_image_media_id' => $category->layout_image_media_id,
                'layout_image' => $category->layoutImage,
                'map_coordinates' => $category->map_coordinates,
            ]);
    }

    public function publishArticle(HandbookArticle|int|string $article, ?int $userId = null): HandbookArticle
    {
        return DB::transaction(function () use ($article, $userId): HandbookArticle {
            $record = $this->resolveArticle($article);

            if (! $record->content || ! $record->category_id) {
                throw new InvalidArgumentException('Handbook article requires content and category before publishing.');
            }

            $record->forceFill([
                'status' => 'published',
                'user_id' => $record->user_id ?? $userId,
            ])->save();

            return $record->refresh()->load(['category', 'metadata', 'relatedItems']);
        });
    }

    public function metadataGrouped(HandbookArticle|int|string $article): Collection
    {
        $record = $this->resolveArticle($article);

        return $this->metadata->newQuery()
            ->where('article_id', $record->id)
            ->orderBy('meta_type')
            ->orderBy('meta_key')
            ->get()
            ->groupBy('meta_type');
    }

    public function linkRelatedItem(
        HandbookArticle|int|string $article,
        string $relatableType,
        int $relatableId,
        string $relationType,
        int $sortOrder = 0,
    ): HandbookRelatedItem {
        if (! in_array($relationType, ['calculation_tool', 'library_item', 'partner_presentation', 'ai_shortcut'], true)) {
            throw new InvalidArgumentException('Invalid handbook relation type.');
        }

        return DB::transaction(function () use ($article, $relatableType, $relatableId, $relationType, $sortOrder): HandbookRelatedItem {
            $record = $this->resolveArticle($article);

            return $this->relatedItems->newQuery()->updateOrCreate(
                [
                    'handbook_article_id' => $record->id,
                    'relatable_type' => $relatableType,
                    'relatable_id' => $relatableId,
                ],
                [
                    'relation_type' => $relationType,
                    'sort_order' => $sortOrder,
                ],
            );
        });
    }

    public function searchArticles(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->articles->newQuery()
            ->with(['category.plantType', 'metadata', 'relatedItems'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['published'] ?? false, fn (Builder $query) => $query->published())
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['plant_type_id'] ?? null, function (Builder $query, int $plantTypeId): void {
                $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('plant_type_id', $plantTypeId));
            })
            ->when($filters['ai_trainable'] ?? null, fn (Builder $query, bool $aiTrainable) => $query->where('is_ai_trainable', $aiTrainable))
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $innerQuery) use ($term): void {
                    $innerQuery
                        ->where('title', 'like', "%{$term}%")
                        ->orWhere('summary', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%")
                        ->orWhere('optimization_guidance', 'like', "%{$term}%")
                        ->orWhere('process_description', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    private function resolveArticle(HandbookArticle|int|string $article): HandbookArticle
    {
        if ($article instanceof HandbookArticle) {
            return $article;
        }

        $query = $this->articles->newQuery();
        $record = is_numeric($article)
            ? $query->find((int) $article)
            : $query->where('slug', $article)->first();

        if (! $record) {
            throw (new ModelNotFoundException())->setModel(HandbookArticle::class, [$article]);
        }

        return $record;
    }

    private function formatCategoryNode(HandbookCategory $category, Collection $categories): array
    {
        return [
            'id' => $category->id,
            'title' => $category->title,
            'slug' => $category->slug,
            'plant_type_id' => $category->plant_type_id,
            'map_coordinates' => $category->map_coordinates,
            'sort_order' => $category->sort_order,
            'status' => $category->status,
            'children' => $categories
                ->where('parent_id', $category->id)
                ->values()
                ->map(fn (HandbookCategory $child) => $this->formatCategoryNode($child, $categories)),
        ];
    }
}
