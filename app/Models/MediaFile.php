<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaFile extends Model
{
    protected $fillable = [
        'uploader_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'attachable_type',
        'attachable_id',
        'upload_context',
        'file_category',
        'sort_order',
        'is_watermarked',
        'watermarked_file_path',
        'streaming_url',
        'extracted_text',
        'processing_status',
        'processing_error',
        'is_orphan',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'attachable_id' => 'integer',
            'sort_order' => 'integer',
            'is_watermarked' => 'boolean',
            'is_orphan' => 'boolean',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
