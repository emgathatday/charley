<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'logo_media_id',
        'overview',
        'partner_tier',
        'plant_type_id',
        'company_type',
        'active_partner_subscription_id',
        'keywords',
        'references',
        'contact_email',
        'phone',
        'address',
        'country',
        'website',
        'founded_year',
        'social_links',
        'layout_template',
        'feed_highlight_enabled',
        'subscription_status',
        'subscription_expires_at',
        'approval_status',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'logo_media_id' => 'integer',
            'plant_type_id' => 'integer',
            'active_partner_subscription_id' => 'integer',
            'keywords' => 'array',
            'references' => 'array',
            'founded_year' => 'integer',
            'social_links' => 'array',
            'feed_highlight_enabled' => 'boolean',
            'subscription_expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logoMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'logo_media_id');
    }

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function activePartnerSubscription(): BelongsTo
    {
        return $this->belongsTo(PartnerSubscription::class, 'active_partner_subscription_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(PartnerSubscription::class, 'user_id', 'user_id');
    }

    public function profilePlantTypes(): HasMany
    {
        return $this->hasMany(PartnerProfilePlantType::class);
    }

    public function plantTypes(): BelongsToMany
    {
        return $this->belongsToMany(PlantType::class, 'partner_profile_plant_type')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('plant_types.name');
    }

    public function primaryPlantTypes(): BelongsToMany
    {
        return $this->plantTypes()->wherePivot('is_primary', true);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PartnerProduct::class, 'partner_id');
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(PartnerPresentation::class, 'partner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(PartnerMember::class, 'partner_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeHighlighted(Builder $query): Builder
    {
        return $query->where('feed_highlight_enabled', true);
    }

    public function scopeForPlantType(Builder $query, int|string $plantTypeId): Builder
    {
        return $query->where(function (Builder $query) use ($plantTypeId): void {
            $query->where('plant_type_id', $plantTypeId)
                ->orWhereHas('plantTypes', fn (Builder $query) => $query->where('plant_types.id', $plantTypeId));
        });
    }
}
