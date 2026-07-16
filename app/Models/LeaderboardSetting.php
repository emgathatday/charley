<?php

namespace App\Models;

use Database\Factories\QaLeaderboardSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderboardSetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'min_points_threshold',
        'top_n',
        'effective_from',
    ];

    protected $casts = [
        'min_points_threshold' => 'integer',
        'top_n' => 'integer',
        'effective_from' => 'date',
    ];

    protected static function newFactory(): QaLeaderboardSettingFactory
    {
        return QaLeaderboardSettingFactory::new();
    }
}
