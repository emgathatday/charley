<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'knowledge_domain_id',
        'question_text',
        'question_type',
        'options',
        'correct_answer',
        'points',
        'explanation',
        'sort_order',
        'question_image_media_id',
        'difficulty_level',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
        'points' => 'integer',
        'sort_order' => 'integer',
    ];

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class);
    }

    public function questionImageMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'question_image_media_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(QuizQuestionChoice::class, 'question_id');
    }

    public function attemptQuestions(): HasMany
    {
        return $this->hasMany(QuizAttemptQuestion::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
