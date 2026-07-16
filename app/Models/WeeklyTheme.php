<?php

namespace App\Models;

use Database\Factories\QaWeeklyThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'week_start_date',
        'week_end_date',
        'created_by_admin_id',
        'status',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
    ];

    protected static function newFactory(): QaWeeklyThemeFactory
    {
        return QaWeeklyThemeFactory::new();
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
