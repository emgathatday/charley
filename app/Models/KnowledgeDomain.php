<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'created_by',
        'plant_type_id',
        'icon',
        'total_question_count',
        'quiz_question_count',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'total_question_count' => 'integer',
        'quiz_question_count' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function plantTypes(): BelongsToMany
    {
        return $this->belongsToMany(PlantType::class, 'knowledge_domain_plant_type')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('plant_types.name');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function userDomainExpertise(): HasMany
    {
        return $this->hasMany(UserDomainExpertise::class);
    }

    public function mandatoryQuizDomains(): HasMany
    {
        return $this->hasMany(MandatoryQuizDomain::class);
    }

    public function rankPromotionQuizLogs(): HasMany
    {
        return $this->hasMany(RankPromotionQuizLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
