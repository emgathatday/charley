<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryAccessLog extends Model
{
    use HasFactory;

    public const ACTION_VIEW = 'view';

    public const ACTION_DOWNLOAD = 'download';

    public const ACTIONS = [
        self::ACTION_VIEW,
        self::ACTION_DOWNLOAD,
    ];

    public $timestamps = false;

    protected $fillable = [
        'library_item_id',
        'user_id',
        'action',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class, 'library_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeViews(Builder $query): Builder
    {
        return $query->where('action', self::ACTION_VIEW);
    }

    public function scopeDownloads(Builder $query): Builder
    {
        return $query->where('action', self::ACTION_DOWNLOAD);
    }
}
