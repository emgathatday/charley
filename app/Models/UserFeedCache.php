<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserFeedCache extends Model
{
    use HasFactory;

    public const SOURCE_REASONS = [
        'priority_rule',
        'followed_partner',
        'network_activity',
        'unanswered_question',
        'fresh_content',
        'admin_highlight',
    ];

    public $timestamps = false;

    protected $table = 'user_feed_cache';

    protected $fillable = [
        'user_id',
        'feedable_type',
        'feedable_id',
        'priority_score',
        'source_reason',
        'is_seen',
        'created_at',
        'expires_at',
    ];

    protected $casts = [
        'priority_score' => 'integer',
        'is_seen' => 'boolean',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feedable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUnseen(Builder $query): Builder
    {
        return $query->where('is_seen', false);
    }

    public function scopeFresh(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }
}
