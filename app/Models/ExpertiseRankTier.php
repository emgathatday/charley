<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpertiseRankTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_years_experience',
        'default_cap_percentage',
        'rank_order',
        'required_quiz_count',
        'required_mandatory_quiz_count',
        'status',
        'is_active',
    ];

    protected $casts = [
        'min_years_experience' => 'integer',
        'default_cap_percentage' => 'decimal:2',
        'rank_order' => 'integer',
        'required_quiz_count' => 'integer',
        'required_mandatory_quiz_count' => 'integer',
        'status' => 'string',
        'is_active' => 'boolean',
    ];

    public function userExpertiseRanks(): HasMany
    {
        return $this->hasMany(UserExpertiseRank::class, 'rank_tier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_active', true);
    }
}
