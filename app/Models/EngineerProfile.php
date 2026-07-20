<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EngineerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'photo_media_id',
        'bio',
        'current_company',
        'position',
        'plant_name',
        'experience_years',
        'education',
        'expertise_tags',
        'industry_specialization',
        'searchable_keywords',
        'references',
        'phone',
        'linkedin_url',
        'job_availability',
        'reputation_points',
        'reputation_breakdown',
        'ai_usage_count',
        'is_discoverable',
        'privacy_settings',
        'notification_preferences',
        'verification_document_media_id',
        'verification_renewed_at',
        'renewal_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'expertise_tags' => 'array',
            'industry_specialization' => 'array',
            'searchable_keywords' => 'array',
            'references' => 'array',
            'reputation_points' => 'integer',
            'reputation_breakdown' => 'array',
            'ai_usage_count' => 'integer',
            'is_discoverable' => 'boolean',
            'privacy_settings' => 'array',
            'notification_preferences' => 'array',
            'verification_renewed_at' => 'datetime',
            'renewal_reminder_sent_at' => 'datetime',
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

    public function verificationDocumentMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'verification_document_media_id');
    }

    public function plantTypes(): BelongsToMany
    {
        return $this->belongsToMany(PlantType::class, 'engineer_profile_plant_type')
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

    public function scopeOpenToWork(Builder $query): Builder
    {
        return $query->whereIn('job_availability', ['open', 'open_to_opportunities']);
    }

    public function scopeForPlantType(Builder $query, int|string $plantTypeId): Builder
    {
        return $query->whereHas('plantTypes', fn (Builder $query) => $query->where('plant_types.id', $plantTypeId));
    }
}
