<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'display_name',
        'description',
        'monthly_price',
        'billing_cycle',
        'duration_days',
        'sort_order',
        'is_public',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'duration_days' => 'integer',
            'sort_order' => 'integer',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function partnerSubscriptions(): HasMany
    {
        return $this->hasMany(PartnerSubscription::class, 'tier_id');
    }

    public function tierPermissions(): HasMany
    {
        return $this->hasMany(SubscriptionTierPermission::class, 'tier_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPermission::class, 'subscription_tier_permissions', 'tier_id', 'permission_id')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }
}
