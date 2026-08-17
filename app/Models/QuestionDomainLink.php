<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionDomainLink extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'knowledge_domain_id',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function knowledgeDomain(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDomain::class);
    }
}
