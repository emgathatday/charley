<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserExpertiseRank extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rank_tier_id',
        'promotion_source',
        'promoted_by',
        'promotion_note',
        'effective_at',
        'is_current',
    ];

    protected $casts = [
        'effective_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rankTier(): BelongsTo
    {
        return $this->belongsTo(ExpertiseRankTier::class, 'rank_tier_id');
    }

    public function promotedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }

    public function rankPromotionLogs(): HasMany
    {
        return $this->hasMany(RankPromotionQuizLog::class, 'resulted_promotion_id');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
