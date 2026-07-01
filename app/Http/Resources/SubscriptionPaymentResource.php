<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'partner_subscription_id' => $this->partner_subscription_id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'payment_proof_media_id' => $this->payment_proof_media_id,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'status' => $this->status,
            'transaction_code' => $this->transaction_code,
            'approved_by' => $this->approved_by,
            'payment_proof_media' => new MediaFileResource($this->whenLoaded('paymentProofMedia')),
        ];
    }
}
