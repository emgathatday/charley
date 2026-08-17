<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsageCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_subscription_id',
        'permission_id',
        'period',
        'used_count',
        'quota_limit',
        'reset_at',
    ];

    protected function casts(): array
    {
        return [
            'used_count' => 'integer',
            'quota_limit' => 'integer',
            'reset_at' => 'datetime',
        ];
    }

    public function partnerSubscription(): BelongsTo
    {
        return $this->belongsTo(PartnerSubscription::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPermission::class, 'permission_id');
    }
}
