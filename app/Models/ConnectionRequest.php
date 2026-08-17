<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectionRequest extends Model
{
    protected $fillable = [
        'requester_id',
        'target_user_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'connection_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }
}
