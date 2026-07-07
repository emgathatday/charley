<?php

namespace App\Services\Library;

use App\Models\LibraryItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LibraryContentService
{
    public function listPublished(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return LibraryItem::query()
            ->with(['category', 'plantType', 'fileMedia'])
            ->published()
            ->approved()
            ->when($filters['category_id'] ?? null, fn ($query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['plant_type_id'] ?? null, fn ($query, int $plantTypeId) => $query->where('plant_type_id', $plantTypeId))
            ->when($filters['content_type'] ?? null, fn ($query, string $contentType) => $query->where('content_type', $contentType))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            }))
            ->latest('approved_at')
            ->paginate($perPage);
    }

    public function create(array $attributes, ?User $author = null): LibraryItem
    {
        return DB::transaction(function () use ($attributes, $author): LibraryItem {
            $attributes['slug'] = $this->uniqueSlug($attributes['slug'] ?? $attributes['title']);
            $attributes['user_id'] = $attributes['user_id'] ?? $author?->id;
            $attributes['status'] = $attributes['status'] ?? LibraryItem::STATUS_DRAFT;

            return LibraryItem::query()->create($this->itemAttributes($attributes));
        });
    }

    public function update(LibraryItem $item, array $attributes): LibraryItem
    {
        return DB::transaction(function () use ($item, $attributes): LibraryItem {
            if (array_key_exists('slug', $attributes) || array_key_exists('title', $attributes)) {
                $attributes['slug'] = $this->uniqueSlug($attributes['slug'] ?? $attributes['title'], $item);
            }

            $item->fill($this->itemAttributes($attributes));
            $item->save();

            return $item->refresh();
        });
    }

    public function approve(LibraryItem $item, User $approver): LibraryItem
    {
        return DB::transaction(function () use ($item, $approver): LibraryItem {
            $item->forceFill([
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'status' => LibraryItem::STATUS_PUBLISHED,
            ])->save();

            return $item->refresh();
        });
    }

    public function archive(LibraryItem $item): LibraryItem
    {
        return DB::transaction(function () use ($item): LibraryItem {
            $item->forceFill(['status' => LibraryItem::STATUS_ARCHIVED])->save();

            return $item->refresh();
        });
    }

    public function aiTrainableContent(int $limit = 100): Collection
    {
        return LibraryItem::query()
            ->with(['category', 'plantType', 'fileMedia'])
            ->published()
            ->approved()
            ->aiTrainable()
            ->whereNotNull('content')
            ->orderByDesc('approved_at')
            ->limit($limit)
            ->get();
    }

    public function assertUsesMediaRegistry(array $attributes): void
    {
        foreach (['file_path', 'path', 'raw_file_path'] as $blockedField) {
            if (array_key_exists($blockedField, $attributes)) {
                throw ValidationException::withMessages([
                    $blockedField => 'Library content must reference media_files through file_media_id.',
                ]);
            }
        }
    }

    private function uniqueSlug(string $value, ?LibraryItem $ignore = null): string
    {
        $slug = Str::slug($value);

        if ($slug === '') {
            throw ValidationException::withMessages(['slug' => 'The library item slug cannot be empty.']);
        }

        $candidate = $slug;
        $suffix = 2;

        while (LibraryItem::query()->where('slug', $candidate)->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function itemAttributes(array $attributes): array
    {
        $this->assertUsesMediaRegistry($attributes);

        return collect($attributes)
            ->only([
                'category_id',
                'user_id',
                'title',
                'slug',
                'summary',
                'content',
                'plant_type_id',
                'author',
                'source',
                'published_year',
                'access_level',
                'download_allowed',
                'copy_paste_disabled',
                'download_count',
                'status',
                'is_ai_trainable',
                'content_type',
                'item_type',
                'view_count',
                'approved_by',
                'approved_at',
                'year',
                'file_media_id',
            ])
            ->all();
    }
}
