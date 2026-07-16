<?php

namespace App\Models;

use Database\Factories\QaQuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'posted_by_admin_id',
        'on_behalf_of_partner_id',
        'weekly_theme_id',
        'plant_type_id',
        'title',
        'body',
        'is_anonymous',
        'status',
        'attachment_media_ids',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'attachment_media_ids' => 'array',
    ];

    protected static function newFactory(): QaQuestionFactory
    {
        return QaQuestionFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_admin_id');
    }

    public function onBehalfOfPartner(): BelongsTo
    {
        return $this->belongsTo(PartnerProfile::class, 'on_behalf_of_partner_id');
    }

    public function weeklyTheme(): BelongsTo
    {
        return $this->belongsTo(WeeklyTheme::class);
    }

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function domainLinks(): HasMany
    {
        return $this->hasMany(QuestionDomainLink::class);
    }

    public function knowledgeDomains(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeDomain::class, 'question_domain_links');
    }

    public function attachmentMediaFiles(): Builder
    {
        return MediaFile::query()->whereIn('id', $this->attachment_media_ids ?? []);
    }
}
