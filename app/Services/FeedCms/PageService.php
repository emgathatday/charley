<?php

namespace App\Services\FeedCms;

use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PageService
{
    public function __construct(private readonly PageRevisionService $revisions)
    {
    }

    public function create(array $attributes, ?User $author = null): Page
    {
        return DB::transaction(function () use ($attributes, $author): Page {
            $attributes['slug'] = $this->uniqueSlug($attributes['slug'] ?? $attributes['title']);
            $attributes['user_id'] = $attributes['user_id'] ?? $author?->id;
            $attributes['status'] = $attributes['status'] ?? Page::STATUS_DRAFT;

            $page = Page::query()->create($this->pageAttributes($attributes));

            if ($author) {
                $this->revisions->createRevision($page, $author, 'Initial page content.');
            }

            return $page;
        });
    }

    public function update(Page $page, array $attributes, ?User $changedBy = null): Page
    {
        return DB::transaction(function () use ($page, $attributes, $changedBy): Page {
            if ($changedBy && array_key_exists('content_blocks', $attributes)) {
                $this->revisions->createRevision($page, $changedBy, 'Snapshot before page update.');
            }

            if (array_key_exists('slug', $attributes) || array_key_exists('title', $attributes)) {
                $attributes['slug'] = $this->uniqueSlug($attributes['slug'] ?? $attributes['title'], $page);
            }

            $page->fill($this->pageAttributes($attributes));
            $page->save();

            if ($changedBy && array_key_exists('content_blocks', $attributes)) {
                $this->revisions->createRevision($page->refresh(), $changedBy, 'Page content updated.');
            }

            return $page->refresh();
        });
    }

    public function publish(Page $page, ?User $changedBy = null): Page
    {
        return DB::transaction(function () use ($page, $changedBy): Page {
            if ($changedBy) {
                $this->revisions->createRevision($page, $changedBy, 'Snapshot before publish.');
            }

            $page->forceFill([
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
            ])->save();

            return $page->refresh();
        });
    }

    public function archive(Page $page, ?User $changedBy = null): Page
    {
        return DB::transaction(function () use ($page, $changedBy): Page {
            if ($changedBy) {
                $this->revisions->createRevision($page, $changedBy, 'Snapshot before archive.');
            }

            $page->forceFill([
                'status' => Page::STATUS_ARCHIVED,
            ])->save();

            return $page->refresh();
        });
    }

    private function uniqueSlug(string $value, ?Page $ignore = null): string
    {
        $slug = Str::slug($value);

        if ($slug === '') {
            throw ValidationException::withMessages(['slug' => 'The page slug cannot be empty.']);
        }

        $candidate = $slug;
        $suffix = 2;

        while (Page::query()->where('slug', $candidate)->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function pageAttributes(array $attributes): array
    {
        return collect($attributes)
            ->only(['title', 'slug', 'content_blocks', 'status', 'is_system_page', 'view_count', 'seo_meta', 'user_id', 'published_at'])
            ->all();
    }
}
