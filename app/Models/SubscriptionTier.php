<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionTier extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'monthly_price',
        'ai_monthly_limit',
        'announcement_frequency',
        'announcement_limit',
        'can_host_webinar',
        'can_initiate_message',
        'can_create_poll',
        'can_publish_events',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'ai_monthly_limit' => 'integer',
            'announcement_limit' => 'integer',
            'can_host_webinar' => 'boolean',
            'can_initiate_message' => 'boolean',
            'can_create_poll' => 'boolean',
            'can_publish_events' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function partnerSubscriptions(): HasMany
    {
        return $this->hasMany(PartnerSubscription::class, 'tier_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
