<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QaModerationWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'warnable_type',
        'warnable_id',
        'source',
        'severity',
        'reason',
        'evidence',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function warnable(): MorphTo
    {
        return $this->morphTo();
    }
}
