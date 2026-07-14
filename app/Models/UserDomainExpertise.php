<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDomainExpertise extends Model
{
    use HasFactory;

    protected $table = 'user_domain_expertise';

    protected $fillable = [
        'user_id',
        'knowledge_domain_id',
        'self_rated_percentage',
        'is_quiz_unlocked',
        'unlocked_at',
        'unlocked_via_attempt_id',
        'is_top_5_displayed',
        'sort_order',
    ];

    protected $casts = [
        'self_rated_percentage' => 'decimal:2',
        'is_quiz_unlocked' => 'boolean',
        'unlocked_at' => 'datetime',
        'is_top_5_displayed' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class);
    }

    public function unlockedViaAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'unlocked_via_attempt_id');
    }
}
