<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageFeedPriority extends Model
{
    use HasFactory;

    public const CONTENT_TYPES = [
        'partner_announcement',
        'network_post',
        'unanswered_question',
        'library_item',
        'handbook_article',
        'event',
        'job',
        'poll',
        'service',
    ];

    protected $fillable = [
        'content_type',
        'priority_weight',
        'is_highlighted',
        'highlight_color',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'priority_weight' => 'integer',
        'is_highlighted' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeHighlighted(Builder $query): Builder
    {
        return $query->where('is_highlighted', true);
    }
}
