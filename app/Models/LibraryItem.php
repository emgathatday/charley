<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'user_id',
        'title',
        'slug',
        'summary',
        'content',
        'plant_type_id',
        'author',
        'source',
        'published_year',
        'access_level',
        'download_allowed',
        'copy_paste_disabled',
        'download_count',
        'status',
        'is_ai_trainable',
        'content_type',
        'item_type',
        'view_count',
        'approved_by',
        'approved_at',
        'year',
        'file_media_id',
    ];

    protected $casts = [
        'published_year' => 'integer',
        'download_allowed' => 'boolean',
        'copy_paste_disabled' => 'boolean',
        'download_count' => 'integer',
        'is_ai_trainable' => 'boolean',
        'view_count' => 'integer',
        'approved_at' => 'datetime',
        'year' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LibraryCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function fileMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'file_media_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(LibraryAccessLog::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeAiTrainable($query)
    {
        return $query->where('is_ai_trainable', true);
    }
}
