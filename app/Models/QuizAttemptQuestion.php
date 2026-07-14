<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptQuestion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'selected_choice_id',
        'is_correct',
        'sort_order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }

    public function selectedChoice(): BelongsTo
    {
        return $this->belongsTo(QuizQuestionChoice::class, 'selected_choice_id');
    }
}
