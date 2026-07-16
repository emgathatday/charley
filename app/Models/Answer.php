<?php

namespace App\Models;

use Database\Factories\QaAnswerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'user_id',
        'is_anonymous',
        'body',
        'is_admin_featured',
        'confidence_level',
        'admin_rank_order',
        'attachment_media_ids',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_admin_featured' => 'boolean',
        'admin_rank_order' => 'integer',
        'attachment_media_ids' => 'array',
    ];

    protected static function newFactory(): QaAnswerFactory
    {
        return QaAnswerFactory::new();
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachmentMediaFiles(): Builder
    {
        return MediaFile::query()->whereIn('id', $this->attachment_media_ids ?? []);
    }
}
