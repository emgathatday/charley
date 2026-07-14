<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpertiseLevel extends Model
{
    use HasFactory;

    public const INDUSTRY_PROFESSIONAL = 'industry_professional';

    public const EXPERIENCED_PROFESSIONAL = 'experienced_professional';

    public const SENIOR_INDUSTRY_EXPERT = 'senior_industry_expert';

    public const CODES = [
        self::INDUSTRY_PROFESSIONAL,
        self::EXPERIENCED_PROFESSIONAL,
        self::SENIOR_INDUSTRY_EXPERT,
    ];

    protected $fillable = [
        'name',
        'code',
        'min_years_experience',
        'badge_icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_years_experience' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function quizzesTargeting(): HasMany
    {
        return $this->hasMany(Quiz::class, 'target_expertise_level_id');
    }

    public function userRanks(): HasMany
    {
        return $this->hasMany(UserExpertiseRank::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isHigherThan(?self $level): bool
    {
        return $level === null || $this->sort_order > $level->sort_order;
    }
}
