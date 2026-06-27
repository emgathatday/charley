<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SearchIndexEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'indexable_type',
        'indexable_id',
        'searchable_text',
        'structured_data',
        'search_context',
        'is_discoverable',
        'last_indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'structured_data' => 'array',
            'is_discoverable' => 'boolean',
            'last_indexed_at' => 'datetime',
        ];
    }

    public function indexable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->where('is_discoverable', true);
    }

    public function scopeExpertDirectory(Builder $query): Builder
    {
        return $query->where('search_context', 'expert_directory');
    }

    public function scopePartnerDirectory(Builder $query): Builder
    {
        return $query->where('search_context', 'partner_directory');
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('search_context', 'global');
    }
}
