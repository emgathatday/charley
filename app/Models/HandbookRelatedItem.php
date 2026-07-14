<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HandbookRelatedItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'handbook_article_id',
        'relatable_type',
        'relatable_id',
        'relation_type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'handbook_article_id' => 'integer',
            'relatable_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(HandbookArticle::class, 'handbook_article_id');
    }

    public function relatable(): MorphTo
    {
        return $this->morphTo();
    }
}
