<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountPenalty extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'user_id',
        'action_type',
        'status',
        'reason',
        'evidence_ref',
        'duration_days',
        'starts_at',
        'ends_at',
        'admin_id',
        'resolved_at',
        'resolved_by',
        'resolved_reason',
        'resolved_by_penalty_id',
    ];

    protected function casts(): array
    {
        return [
            'evidence_ref' => 'array',
            'duration_days' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function resolvedByPenalty(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resolved_by_penalty_id');
    }

    public function resolvedPenalties(): HasMany
    {
        return $this->hasMany(self::class, 'resolved_by_penalty_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function scopeForAction(Builder $query, string $actionType): Builder
    {
        return $query->where('action_type', $actionType);
    }
}