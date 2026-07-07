<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryAccessRule extends Model
{
    use HasFactory;

    public const PARTNER_TIERS = [
        'gold',
        'diamond',
        'platinum',
    ];

    protected $fillable = [
        'partner_tier',
        'can_view',
        'can_download',
        'can_copy_paste',
        'requires_watermark',
        'max_downloads_per_month',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_download' => 'boolean',
        'can_copy_paste' => 'boolean',
        'requires_watermark' => 'boolean',
        'max_downloads_per_month' => 'integer',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForTier(Builder $query, string $partnerTier): Builder
    {
        return $query->where('partner_tier', $partnerTier);
    }

    public function allowsDownload(): bool
    {
        return $this->can_view && $this->can_download;
    }

    public function allowsCopyPaste(): bool
    {
        return $this->can_view && $this->can_copy_paste;
    }
}
