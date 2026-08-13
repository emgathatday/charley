<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Connection extends Model
{
    protected $fillable = [
        'requester_id',
        'receiver_id',
        'status',
        'initiated_context',
        'declined_at',
        'accepted_at',
        'blocked_at',
        'blocked_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'initiated_context' => 'string',
            'declined_at' => 'datetime',
            'accepted_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function connectionRequest(): HasOne
    {
        return $this->hasOne(ConnectionRequest::class);
    }
}
