<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPresentation extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'title',
        'slug',
        'description',
        'plant_type_id',
        'equipment_category',
        'page_count',
        'download_allowed',
        'view_count',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'is_ai_trainable',
        'file_media_id',
    ];

    protected function casts(): array
    {
        return [
            'page_count' => 'integer',
            'download_allowed' => 'boolean',
            'view_count' => 'integer',
            'approved_at' => 'datetime',
            'is_ai_trainable' => 'boolean',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(PartnerProfile::class, 'partner_id');
    }

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function fileMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'file_media_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeAiTrainable(Builder $query): Builder
    {
        return $query->where('is_ai_trainable', true);
    }
}
