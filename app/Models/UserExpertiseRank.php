<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserExpertiseRank extends Model
{
    use HasFactory;

    public const SOURCE_CV_REVIEW = 'cv_review';

    public const SOURCE_QUIZ_PASS = 'quiz_pass';

    public const SOURCES = [
        self::SOURCE_CV_REVIEW,
        self::SOURCE_QUIZ_PASS,
    ];

    protected $fillable = [
        'user_id',
        'expertise_level_id',
        'plant_type_id',
        'handbook_category_id',
        'source',
        'assigned_by',
        'quiz_attempt_id',
        'notes',
        'is_current',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'expertise_level_id' => 'integer',
            'plant_type_id' => 'integer',
            'handbook_category_id' => 'integer',
            'assigned_by' => 'integer',
            'quiz_attempt_id' => 'integer',
            'is_current' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expertiseLevel(): BelongsTo
    {
        return $this->belongsTo(ExpertiseLevel::class);
    }

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function handbookCategory(): BelongsTo
    {
        return $this->belongsTo(HandbookCategory::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForScope(Builder $query, ?int $plantTypeId, ?int $handbookCategoryId): Builder
    {
        return $query
            ->when($plantTypeId === null, fn (Builder $query): Builder => $query->whereNull('plant_type_id'), fn (Builder $query): Builder => $query->where('plant_type_id', $plantTypeId))
            ->when($handbookCategoryId === null, fn (Builder $query): Builder => $query->whereNull('handbook_category_id'), fn (Builder $query): Builder => $query->where('handbook_category_id', $handbookCategoryId));
    }

    public function scopeHighestCurrent(Builder $query): Builder
    {
        return $query->current()
            ->join('expertise_levels', 'user_expertise_ranks.expertise_level_id', '=', 'expertise_levels.id')
            ->orderByDesc('expertise_levels.sort_order')
            ->select('user_expertise_ranks.*');
    }

    public function isPlatformScoped(): bool
    {
        return $this->plant_type_id === null && $this->handbook_category_id === null;
    }

    public function isPlantScoped(): bool
    {
        return $this->plant_type_id !== null && $this->handbook_category_id === null;
    }

    public function isSectionScoped(): bool
    {
        return $this->handbook_category_id !== null;
    }
}
