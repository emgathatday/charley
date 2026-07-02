<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPenalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'reason',
        'evidence_ref',
        'duration_days',
        'starts_at',
        'ends_at',
        'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'evidence_ref' => 'array',
            'duration_days' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now())
            ->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function scopeForAction(Builder $query, string $actionType): Builder
    {
        return $query->where('action_type', $actionType);
    }
}
