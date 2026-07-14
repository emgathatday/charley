<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankPromotionQuizLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'quiz_attempt_id',
        'knowledge_domain_id',
        'is_mandatory',
        'promotion_cycle_no',
        'resulted_promotion_id',
        'created_at',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'promotion_cycle_no' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class);
    }

    public function resultedPromotion(): BelongsTo
    {
        return $this->belongsTo(UserExpertiseRank::class, 'resulted_promotion_id');
    }
}
