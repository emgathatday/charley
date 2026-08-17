<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UnverifiedMemberProfile extends Model
{
    protected $fillable = [
        'user_id',
        'photo_media_id',
        'bio',
        'current_institution',
        'field_of_study',
        'experience_years',
        'education',
        'references',
        'expertise_tags',
        'searchable_keywords',
        'is_discoverable',
        'privacy_settings',
        'notification_preferences',
        'linkedin_url',
        'job_availability',
        'verification_intent',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'references' => 'array',
            'expertise_tags' => 'array',
            'searchable_keywords' => 'array',
            'is_discoverable' => 'boolean',
            'privacy_settings' => 'array',
            'notification_preferences' => 'array',
            'verification_intent' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photoMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'photo_media_id');
    }

    public function plantTypes(): BelongsToMany
    {
        return $this->belongsToMany(PlantType::class, 'unverified_member_profile_plant_type')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('plant_types.name');
    }

    public function primaryPlantTypes(): BelongsToMany
    {
        return $this->plantTypes()->wherePivot('is_primary', true);
    }

    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->where('is_discoverable', true);
    }

    public function scopeWantsVerification(Builder $query): Builder
    {
        return $query->where('verification_intent', true);
    }

    public function scopeForPlantType(Builder $query, int|string $plantTypeId): Builder
    {
        return $query->whereHas('plantTypes', fn (Builder $query) => $query->where('plant_types.id', $plantTypeId));
    }
}
