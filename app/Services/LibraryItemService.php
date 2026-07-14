<?php

namespace App\Services;

use App\Models\LibraryAccessLog;
use App\Jobs\ProcessLibraryItemIngestionJob;
use App\Models\LibraryAccessRule;
use App\Models\LibraryItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LibraryItemService
{
    public function __construct(
        private readonly LibraryItem $items,
        private readonly LibraryAccessRule $accessRules,
        private readonly LibraryAccessLog $accessLogs,
    ) {
    }

    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->items->newQuery()
            ->with(['category', 'plantType', 'user', 'approvedBy', 'fileMedia'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['published'] ?? false, fn (Builder $query) => $query->published())
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['plant_type_id'] ?? null, fn (Builder $query, int $plantTypeId) => $query->where('plant_type_id', $plantTypeId))
            ->when($filters['access_level'] ?? null, fn (Builder $query, string $accessLevel) => $query->where('access_level', $accessLevel))
            ->when($filters['content_type'] ?? null, fn (Builder $query, string $contentType) => $query->where('content_type', $contentType))
            ->when(array_key_exists('ai_trainable', $filters), fn (Builder $query) => $query->where('is_ai_trainable', (bool) $filters['ai_trainable']))
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $innerQuery) use ($term): void {
                    $innerQuery
                        ->where('title', 'like', "%{$term}%")
                        ->orWhere('summary', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%")
                        ->orWhere('author', 'like', "%{$term}%")
                        ->orWhere('source', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('approved_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function create(array $data, ?User $actor = null): LibraryItem
    {
        return DB::transaction(function () use ($data, $actor): LibraryItem {
            $payload = $this->sanitizeItemPayload($data);

            if ($actor && empty($payload['user_id'])) {
                $payload['user_id'] = $actor->id;
            }

            $item = $this->items->newQuery()->create($payload);

            return $item->refresh()->load(['category', 'plantType', 'user', 'approvedBy', 'fileMedia']);
        });
    }

    public function update(LibraryItem|int|string $item, array $data): LibraryItem
    {
        return DB::transaction(function () use ($item, $data): LibraryItem {
            $record = $this->resolveItem($item);
            $record->fill($this->sanitizeItemPayload($data, true))->save();

            return $record->refresh()->load(['category', 'plantType', 'user', 'approvedBy', 'fileMedia']);
        });
    }

    public function approve(LibraryItem|int|string $item, User $approver): LibraryItem
    {
        return DB::transaction(function () use ($item, $approver): LibraryItem {
            $record = $this->resolveItem($item);

            if (! $record->title || ! $record->category_id || ! $record->content_type) {
                throw new InvalidArgumentException('Library item requires title, category and content type before approval.');
            }

            $record->forceFill([
                'status' => 'published',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ])->save();

            ProcessLibraryItemIngestionJob::dispatch($record->id)->afterCommit()->onQueue('library');

            return $record->refresh()->load(['category', 'plantType', 'user', 'approvedBy', 'fileMedia']);
        });
    }

    public function archive(LibraryItem|int|string $item): LibraryItem
    {
        return DB::transaction(function () use ($item): LibraryItem {
            $record = $this->resolveItem($item);
            $record->forceFill(['status' => 'archived'])->save();

            return $record->refresh();
        });
    }

    public function accessPolicy(LibraryItem|int|string $item, ?User $user = null, ?string $partnerTier = null): array
    {
        $record = $this->resolveItem($item);
        $accessLevel = $record->access_level;
        $rule = $partnerTier ? $this->accessRules->newQuery()->where('partner_tier', $partnerTier)->first() : null;
        $isAdmin = $user?->role === 'admin';
        $isProfessional = in_array($user?->role, ['admin', 'professional', 'partner'], true);
        $isPartnerTierAllowed = $rule && in_array($accessLevel, ['partner_only', $partnerTier], true);
        $canView = $record->status === 'published'
            && ($accessLevel === 'public' || $isAdmin || ($accessLevel === 'professional_only' && $isProfessional) || $isPartnerTierAllowed);

        if ($rule && ! $rule->can_view) {
            $canView = false;
        }

        $canDownload = $canView && $record->download_allowed && (bool) ($rule?->can_download ?? $isAdmin);
        $canCopyPaste = $canView && ! $record->copy_paste_disabled && (bool) ($rule?->can_copy_paste ?? $isAdmin);

        return [
            'can_view' => $canView,
            'can_download' => $canDownload,
            'can_copy_paste' => $canCopyPaste,
            'requires_watermark' => $canView && (bool) ($rule?->requires_watermark ?? false),
            'max_downloads_per_month' => $rule?->max_downloads_per_month,
        ];
    }

    public function recordView(LibraryItem|int|string $item, User $user, string $ipAddress, ?string $partnerTier = null): LibraryAccessLog
    {
        $record = $this->resolveItem($item);
        $policy = $this->accessPolicy($record, $user, $partnerTier);

        if (! $policy['can_view']) {
            throw new InvalidArgumentException('User is not allowed to view this library item.');
        }

        return DB::transaction(function () use ($record, $user, $ipAddress): LibraryAccessLog {
            $record->increment('view_count');

            return $this->createAccessLog($record, $user, 'view', $ipAddress);
        });
    }

    public function recordDownload(LibraryItem|int|string $item, User $user, string $ipAddress, ?string $partnerTier = null): LibraryAccessLog
    {
        $record = $this->resolveItem($item);
        $policy = $this->accessPolicy($record, $user, $partnerTier);

        if (! $policy['can_download']) {
            throw new InvalidArgumentException('User is not allowed to download this library item.');
        }

        $this->assertMonthlyDownloadLimit($record, $user, $policy['max_downloads_per_month']);

        return DB::transaction(function () use ($record, $user, $ipAddress): LibraryAccessLog {
            $record->increment('download_count');

            return $this->createAccessLog($record, $user, 'download', $ipAddress);
        });
    }

    public function resolveItem(LibraryItem|int|string $item): LibraryItem
    {
        if ($item instanceof LibraryItem) {
            return $item;
        }

        $query = $this->items->newQuery();
        $record = is_numeric($item)
            ? $query->find((int) $item)
            : $query->where('slug', $item)->first();

        if (! $record) {
            throw (new ModelNotFoundException())->setModel(LibraryItem::class, [$item]);
        }

        return $record;
    }

    private function sanitizeItemPayload(array $data, bool $partial = false): array
    {
        $allowed = [
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
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['access_level', 'status', 'content_type'] as $field) {
            if (array_key_exists($field, $payload)) {
                $this->assertEnumValue($field, $payload[$field]);
            }
        }

        if (! $partial && empty($payload['slug']) && ! empty($payload['title'])) {
            throw new InvalidArgumentException('Library item slug is required.');
        }

        return $payload;
    }

    private function assertEnumValue(string $field, string $value): void
    {
        $allowed = [
            'access_level' => ['public', 'professional_only', 'partner_only', 'gold', 'diamond', 'platinum'],
            'status' => ['draft', 'published', 'archived'],
            'content_type' => ['article', 'video', 'document', 'presentation', 'case_study', 'safety_bulletin'],
        ];

        if (! in_array($value, $allowed[$field], true)) {
            throw new InvalidArgumentException("Invalid library item {$field}.");
        }
    }

    private function assertMonthlyDownloadLimit(LibraryItem $item, User $user, ?int $limit): void
    {
        if ($limit === null) {
            return;
        }

        $downloads = $this->accessLogs->newQuery()
            ->where('library_item_id', $item->id)
            ->where('user_id', $user->id)
            ->where('action', 'download')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        if ($downloads >= $limit) {
            throw new InvalidArgumentException('Monthly library download limit reached.');
        }
    }

    private function createAccessLog(LibraryItem $item, User $user, string $action, string $ipAddress): LibraryAccessLog
    {
        return $this->accessLogs->newQuery()->create([
            'library_item_id' => $item->id,
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }
}
