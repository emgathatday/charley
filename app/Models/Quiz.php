<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'knowledge_domain_id',
        'title',
        'slug',
        'description',
        'time_limit_minutes',
        'max_attempts_per_user',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'knowledge_domain_id' => 'integer',
            'time_limit_minutes' => 'integer',
            'max_attempts_per_user' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function bestScores(): HasMany
    {
        return $this->hasMany(UserQuizBestScore::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeForDomain(Builder $query, int $knowledgeDomainId): Builder
    {
        return $query->where('knowledge_domain_id', $knowledgeDomainId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_PUBLISHED]);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function maxPossibleScore(): int
    {
        if ($this->relationLoaded('questions')) {
            return (int) $this->questions->sum('points');
        }

        return (int) $this->questions()->sum('points');
    }
}