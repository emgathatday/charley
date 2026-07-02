<?php

namespace App\Models\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait HasTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function attachTags(array|Collection $tagIds): void
    {
        $this->tags()->syncWithoutDetaching($tagIds);
        $this->refreshTagUsageCounts($tagIds);
    }

    public function syncTags(array|Collection $tagIds): void
    {
        $before = $this->tags()->pluck('tags.id');

        $this->tags()->sync($tagIds);

        $this->refreshTagUsageCounts($before->merge($tagIds)->unique());
    }

    public function detachTags(array|Collection|null $tagIds = null): void
    {
        $affected = $tagIds === null ? $this->tags()->pluck('tags.id') : collect($tagIds);

        $this->tags()->detach($tagIds);
        $this->refreshTagUsageCounts($affected);
    }

    protected function refreshTagUsageCounts(array|Collection $tagIds): void
    {
        collect($tagIds)
            ->flatten()
            ->filter()
            ->unique()
            ->each(function (int|string $tagId): void {
                Tag::query()
                    ->whereKey($tagId)
                    ->update([
                        'usage_count' => $this->tags()
                            ->newPivotStatement()
                            ->where('tag_id', $tagId)
                            ->count(),
                    ]);
            });
    }
}
