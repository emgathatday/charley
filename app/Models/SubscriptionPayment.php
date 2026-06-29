<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_subscription_id',
        'amount',
        'payment_method',
        'payment_proof_media_id',
        'period_start',
        'period_end',
        'status',
        'transaction_code',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function partnerSubscription(): BelongsTo
    {
        return $this->belongsTo(PartnerSubscription::class);
    }

    public function paymentProofMedia(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'payment_proof_media_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
