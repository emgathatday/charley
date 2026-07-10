<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryItemHotspot extends Model
{
    use HasFactory;

    public const SHAPE_RECT = 'rect';

    public const SHAPE_POLYGON = 'polygon';

    public const SHAPE_CIRCLE = 'circle';

    public const SHAPES = [
        self::SHAPE_RECT,
        self::SHAPE_POLYGON,
        self::SHAPE_CIRCLE,
    ];

    protected $fillable = [
        'library_item_id',
        'knowledge_domain_id',
        'label',
        'shape_type',
        'coordinates',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'library_item_id' => 'integer',
            'knowledge_domain_id' => 'integer',
            'coordinates' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function libraryItem(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class);
    }

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class);
    }

    public function scopeForLibraryItem(Builder $query, int $libraryItemId): Builder
    {
        return $query->where('library_item_id', $libraryItemId);
    }

    public function scopeForDomain(Builder $query, int $knowledgeDomainId): Builder
    {
        return $query->where('knowledge_domain_id', $knowledgeDomainId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function displayLabel(): string
    {
        return $this->label ?: (string) $this->knowledgeDomain?->name;
    }
}