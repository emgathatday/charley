<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainRankTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_domain_id',
        'name',
        'min_points',
        'badge_icon',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'knowledge_domain_id' => 'integer',
            'min_points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class);
    }

    public function userDomainPoints(): HasMany
    {
        return $this->hasMany(UserDomainPoint::class, 'current_rank_tier_id');
    }

    public function scopeForDomain(Builder $query, int $knowledgeDomainId): Builder
    {
        return $query->where('knowledge_domain_id', $knowledgeDomainId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function scopeAchievableWith(Builder $query, int $points): Builder
    {
        return $query->where('min_points', '<=', $points);
    }
}