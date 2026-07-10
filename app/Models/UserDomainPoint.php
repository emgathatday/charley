<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDomainPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'knowledge_domain_id',
        'total_points',
        'current_rank_tier_id',
        'last_recalculated_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'knowledge_domain_id' => 'integer',
            'total_points' => 'integer',
            'current_rank_tier_id' => 'integer',
            'last_recalculated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class);
    }

    public function currentRankTier(): BelongsTo
    {
        return $this->belongsTo(DomainRankTier::class, 'current_rank_tier_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDomain(Builder $query, int $knowledgeDomainId): Builder
    {
        return $query->where('knowledge_domain_id', $knowledgeDomainId);
    }

    public function scopeRanked(Builder $query): Builder
    {
        return $query->whereNotNull('current_rank_tier_id');
    }

    public function hasRank(): bool
    {
        return $this->current_rank_tier_id !== null;
    }
}