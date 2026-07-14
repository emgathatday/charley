<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandbookMetadata extends Model
{
    use HasFactory;

    protected $table = 'handbook_metadata';

    protected $fillable = [
        'article_id',
        'meta_type',
        'meta_key',
        'meta_value',
        'vector_status',
    ];

    protected function casts(): array
    {
        return [
            'article_id' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(HandbookArticle::class, 'article_id');
    }
}
