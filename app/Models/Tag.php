<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    public const CATEGORY_TECHNICAL = 'technical';

    public const CATEGORY_PLANT_TYPE = 'plant_type';

    public const CATEGORY_EQUIPMENT = 'equipment';

    public const CATEGORY_PROCESS = 'process';

    public const CATEGORY_GENERAL = 'general';

    public const CATEGORIES = [
        self::CATEGORY_TECHNICAL,
        self::CATEGORY_PLANT_TYPE,
        self::CATEGORY_EQUIPMENT,
        self::CATEGORY_PROCESS,
        self::CATEGORY_GENERAL,
    ];

    protected $fillable = [
        'name',
        'slug',
        'category',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];


    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        return $category ? $query->where('category', $category) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $term
            ? $query->where(fn (Builder $builder): Builder => $builder
                ->where('name', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%"))
            : $query;
    }

    public static function slugFor(string $name): string
    {
        return Str::slug($name);
    }
}
