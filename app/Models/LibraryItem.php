<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryItem extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    public const ACCESS_LEVELS = [
        'public',
        'member',
        'professional_only',
        'partner_only',
        'admin_only',
    ];

    public const CONTENT_TYPES = [
        'article',
        'video',
        'document',
        'presentation',
        'case_study',
        'safety_bulletin',
    ];

    public const ITEM_TYPES = [
        'handbook',
        'article',
        'presentation',
        'video',
        'case_study',
        'safety_bulletin',
        'whitepaper',
    ];

    protected $fillable = [
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

    protected $casts = [
        'published_year' => 'integer',
        'download_allowed' => 'boolean',
        'copy_paste_disabled' => 'boolean',
        'download_count' => 'integer',
        'is_ai_trainable' => 'boolean',
        'view_count' => 'integer',
        'approved_at' => 'datetime',
        'year' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LibraryCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function fileMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'file_media_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(LibraryAccessLog::class, 'library_item_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereNotNull('approved_by')->whereNotNull('approved_at');
    }

    public function scopeAiTrainable(Builder $query): Builder
    {
        return $query->where('is_ai_trainable', true);
    }

    public function scopeForAccessLevel(Builder $query, string $accessLevel): Builder
    {
        return $query->where('access_level', $accessLevel);
    }

    public function scopeForPlantType(Builder $query, int $plantTypeId): Builder
    {
        return $query->where('plant_type_id', $plantTypeId);
    }

    public function canBeViewedByAccessLevel(string $accessLevel): bool
    {
        if ($this->access_level === 'public') {
            return true;
        }

        return $this->access_level === $accessLevel;
    }

    public function canBeDownloadedBy(?LibraryAccessRule $rule): bool
    {
        return $this->download_allowed && (bool) $rule?->can_download;
    }
}
