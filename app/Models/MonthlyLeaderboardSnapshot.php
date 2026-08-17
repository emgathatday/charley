<?php

namespace App\Models;

use Database\Factories\QaMonthlyLeaderboardSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyLeaderboardSnapshot extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'year_month',
        'total_points_in_month',
        'rank_position',
        'created_at',
    ];

    protected $casts = [
        'total_points_in_month' => 'integer',
        'rank_position' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function newFactory(): QaMonthlyLeaderboardSnapshotFactory
    {
        return QaMonthlyLeaderboardSnapshotFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
