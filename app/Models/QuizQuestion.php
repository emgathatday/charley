<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    public const TYPE_SINGLE_CHOICE = 'single_choice';

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPES = [
        self::TYPE_SINGLE_CHOICE,
        self::TYPE_MULTIPLE_CHOICE,
        self::TYPE_TRUE_FALSE,
    ];

    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'options',
        'correct_answer',
        'points',
        'explanation',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'options' => 'array',
            'correct_answer' => 'array',
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function isSingleChoice(): bool
    {
        return $this->question_type === self::TYPE_SINGLE_CHOICE;
    }

    public function isMultipleChoice(): bool
    {
        return $this->question_type === self::TYPE_MULTIPLE_CHOICE;
    }

    public function isTrueFalse(): bool
    {
        return $this->question_type === self::TYPE_TRUE_FALSE;
    }
}