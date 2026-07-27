<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionTierPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'tier_id',
        'permission_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class, 'tier_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPermission::class, 'permission_id');
    }
}
