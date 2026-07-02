<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentApprovalQueue extends Model
{
    use HasFactory;

    protected $table = 'content_approval_queue';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'submitted_by',
        'submitter_tier',
        'content_title',
        'content_type_label',
        'priority',
        'status',
        'assigned_to',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }
}
