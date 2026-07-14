<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HandbookArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'user_id',
        'title',
        'slug',
        'summary',
        'content',
        'optimization_guidance',
        'failure_modes',
        'status',
        'is_ai_trainable',
        'ai_shortcut_config',
        'view_count',
        'process_description',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'user_id' => 'integer',
            'failure_modes' => 'array',
            'is_ai_trainable' => 'boolean',
            'ai_shortcut_config' => 'array',
            'view_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HandbookCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(HandbookMetadata::class, 'article_id');
    }

    public function relatedItems(): HasMany
    {
        return $this->hasMany(HandbookRelatedItem::class);
    }
}
