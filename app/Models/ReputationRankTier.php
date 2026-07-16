<?php

namespace App\Models;

use Database\Factories\QaReputationRankTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReputationRankTier extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'star_level',
        'min_points',
        'label',
    ];

    protected $casts = [
        'star_level' => 'integer',
        'min_points' => 'integer',
    ];

    protected static function newFactory(): QaReputationRankTierFactory
    {
        return QaReputationRankTierFactory::new();
    }

    public function userReputations(): HasMany
    {
        return $this->hasMany(UserReputation::class, 'current_star_rank', 'star_level');
    }
}
