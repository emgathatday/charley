<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
