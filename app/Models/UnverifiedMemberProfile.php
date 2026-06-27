<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->where('is_discoverable', true);
    }

    public function scopeWantsVerification(Builder $query): Builder
    {
        return $query->where('verification_intent', true);
    }
}
