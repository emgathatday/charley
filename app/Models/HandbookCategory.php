<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HandbookCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'plant_type_id',
        'parent_id',
        'layout_image_media_id',
        'map_coordinates',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'plant_type_id' => 'integer',
            'parent_id' => 'integer',
            'layout_image_media_id' => 'integer',
            'map_coordinates' => 'array',
            'sort_order' => 'integer',
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

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function layoutImage(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'layout_image_media_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(HandbookArticle::class, 'category_id');
    }
}
