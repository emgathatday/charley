<?php

namespace App\Models;

use Database\Factories\QaPointTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'points',
        'source_type',
        'source_id',
        'reason',
        'performed_by',
        'created_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'source_id' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function newFactory(): QaPointTransactionFactory
    {
        return QaPointTransactionFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
