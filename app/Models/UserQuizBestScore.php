<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuizBestScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'best_score',
        'best_quiz_attempt_id',
        'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'quiz_id' => 'integer',
            'best_score' => 'integer',
            'best_quiz_attempt_id' => 'integer',
            'achieved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function bestQuizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'best_quiz_attempt_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForQuiz(Builder $query, int $quizId): Builder
    {
        return $query->where('quiz_id', $quizId);
    }
}