<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'knowledge_domain_id',
        'attempt_number',
        'answers_submitted',
        'score',
        'max_possible_score',
        'total_questions',
        'correct_count',
        'score_percentage',
        'pass_threshold',
        'is_passed',
        'started_at',
        'completed_at',
        'submitted_at',
        'next_attempt_allowed_at',
        'counted_for_rank_promotion',
    ];

    protected $casts = [
        'answers_submitted' => 'array',
        'attempt_number' => 'integer',
        'score' => 'integer',
        'max_possible_score' => 'integer',
        'total_questions' => 'integer',
        'correct_count' => 'integer',
        'score_percentage' => 'decimal:2',
        'pass_threshold' => 'decimal:2',
        'is_passed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'next_attempt_allowed_at' => 'datetime',
        'counted_for_rank_promotion' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class);
    }

    public function attemptQuestions(): HasMany
    {
        return $this->hasMany(QuizAttemptQuestion::class);
    }

    public function unlockedDomainExpertise(): HasMany
    {
        return $this->hasMany(UserDomainExpertise::class, 'unlocked_via_attempt_id');
    }

    public function rankPromotionLogs(): HasMany
    {
        return $this->hasMany(RankPromotionQuizLog::class);
    }

    public function scopePassed($query)
    {
        return $query->where('is_passed', true);
    }
}
