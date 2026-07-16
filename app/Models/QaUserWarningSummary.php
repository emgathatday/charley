<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaUserWarningSummary extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'confirmed_warning_count',
        'last_warning_at',
        'is_frozen',
        'frozen_at',
        'frozen_reason',
        'updated_at',
    ];

    protected $casts = [
        'confirmed_warning_count' => 'integer',
        'last_warning_at' => 'datetime',
        'is_frozen' => 'boolean',
        'frozen_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
