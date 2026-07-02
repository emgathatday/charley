<?php

namespace App\Services\Taxonomy;

use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxonomyService
{
    public function normalizeSlug(string $value): string
    {
        $slug = Tag::slugFor($value);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => 'The tag slug cannot be empty.',
            ]);
        }

        return $slug;
    }

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Tag::query()
            ->category($filters['category'] ?? null)
            ->search($filters['search'] ?? null)
            ->orderBy('category')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function search(?string $term = null, ?string $category = null, int $limit = 20): Collection
    {
        return Tag::query()
            ->category($category)
            ->search($term)
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function create(array $attributes): Tag
    {
        return DB::transaction(function () use ($attributes): Tag {
            $attributes['slug'] = $this->uniqueSlug(
                $attributes['slug'] ?? $attributes['name'],
            );
            $attributes['usage_count'] = $attributes['usage_count'] ?? 0;

            return Tag::query()->create($this->onlyTagAttributes($attributes));
        });
    }

    public function update(Tag $tag, array $attributes): Tag
    {
        return DB::transaction(function () use ($tag, $attributes): Tag {
            if (array_key_exists('slug', $attributes) || array_key_exists('name', $attributes)) {
                $tag->slug = $this->uniqueSlug(
                    $attributes['slug'] ?? $attributes['name'],
                    $tag,
                );
            }

            $tag->fill($this->onlyTagAttributes($attributes, ['slug']));
            $tag->save();

            return $tag->refresh();
        });
    }

    public function delete(Tag $tag): void
    {
        DB::transaction(fn (): ?bool => $tag->delete());
    }

    public function attach(Model $model, array|Collection $tagIds): void
    {
        $this->assertTaggable($model);

        DB::transaction(function () use ($model, $tagIds): void {
            $model->tags()->syncWithoutDetaching($tagIds);
            $this->recalculateUsageCounts($tagIds);
        });
    }

    public function sync(Model $model, array|Collection $tagIds): void
    {
        $this->assertTaggable($model);

        DB::transaction(function () use ($model, $tagIds): void {
            $before = $model->tags()->pluck('tags.id');
            $model->tags()->sync($tagIds);
            $this->recalculateUsageCounts($before->merge($tagIds)->unique());
        });
    }

    public function detach(Model $model, array|Collection|null $tagIds = null): void
    {
        $this->assertTaggable($model);

        DB::transaction(function () use ($model, $tagIds): void {
            $affected = $tagIds === null ? $model->tags()->pluck('tags.id') : collect($tagIds);
            $model->tags()->detach($tagIds);
            $this->recalculateUsageCounts($affected);
        });
    }

    public function recalculateUsageCounts(array|Collection|null $tagIds = null): void
    {
        $query = Tag::query();

        if ($tagIds !== null) {
            $query->whereKey(collect($tagIds)->flatten()->filter()->unique()->values());
        }

        $query->get()->each(function (Tag $tag): void {
            $tag->forceFill([
                'usage_count' => DB::table('taggables')
                    ->where('tag_id', $tag->id)
                    ->count(),
            ])->save();
        });
    }

    private function uniqueSlug(string $value, ?Tag $ignore = null): string
    {
        $slug = $this->normalizeSlug($value);
        $candidate = $slug;
        $suffix = 2;

        while ($this->slugExists($candidate, $ignore)) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function slugExists(string $slug, ?Tag $ignore = null): bool
    {
        return Tag::query()
            ->where('slug', $slug)
            ->when($ignore, fn ($query): mixed => $query->whereKeyNot($ignore->getKey()))
            ->exists();
    }

    private function onlyTagAttributes(array $attributes, array $except = []): array
    {
        return collect($attributes)
            ->only(['name', 'slug', 'category', 'usage_count'])
            ->except($except)
            ->all();
    }

    private function assertTaggable(Model $model): void
    {
        if (! method_exists($model, 'tags')) {
            throw ValidationException::withMessages([
                'taggable' => 'The model does not support taxonomy tags.',
            ]);
        }
    }
}
