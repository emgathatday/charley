<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period',
        'used_count',
        'quota_limit',
    ];

    protected function casts(): array
    {
        return [
            'used_count' => 'integer',
            'quota_limit' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
