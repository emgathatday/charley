<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'attempt_number',
        'answers_submitted',
        'score',
        'max_possible_score',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'user_id' => 'integer',
            'attempt_number' => 'integer',
            'answers_submitted' => 'array',
            'score' => 'integer',
            'max_possible_score' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bestScore(): HasOne
    {
        return $this->hasOne(UserQuizBestScore::class, 'best_quiz_attempt_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForQuiz(Builder $query, int $quizId): Builder
    {
        return $query->where('quiz_id', $quizId);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function scorePercent(): float
    {
        if ($this->max_possible_score <= 0) {
            return 0.0;
        }

        return round(($this->score / $this->max_possible_score) * 100, 2);
    }
}