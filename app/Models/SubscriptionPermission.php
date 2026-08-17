<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'module',
        'value_type',
        'default_value',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_value' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tierPermissions(): HasMany
    {
        return $this->hasMany(SubscriptionTierPermission::class, 'permission_id');
    }

    public function tiers(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionTier::class, 'subscription_tier_permissions', 'permission_id', 'tier_id')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function usageCounters(): HasMany
    {
        return $this->hasMany(SubscriptionUsageCounter::class, 'permission_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
