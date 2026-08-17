<?php

namespace App\Models;

use Database\Factories\QaUserReputationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReputation extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    protected $table = 'user_reputation';

    protected $fillable = [
        'user_id',
        'total_points',
        'current_star_rank',
        'updated_at',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'current_star_rank' => 'integer',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): QaUserReputationFactory
    {
        return QaUserReputationFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentRankTier(): BelongsTo
    {
        return $this->belongsTo(ReputationRankTier::class, 'current_star_rank', 'star_level');
    }
}
