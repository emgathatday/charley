<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPaymentRequest;
use App\Http\Resources\SubscriptionPaymentResource;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionPaymentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = SubscriptionPayment::query()
            ->with(['partnerSubscription', 'paymentProofMedia'])
            ->when($request->filled('partner_subscription_id'), fn ($query) => $query->where('partner_subscription_id', $request->integer('partner_subscription_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return SubscriptionPaymentResource::collection($payments);
    }

    public function store(SubscriptionPaymentRequest $request): SubscriptionPaymentResource
    {
        $payment = SubscriptionPayment::create([
            ...$request->validated(),
            'payment_method' => $request->input('payment_method', 'bank_transfer'),
            'status' => $request->input('status', 'pending'),
        ]);

        return new SubscriptionPaymentResource($payment->load(['partnerSubscription', 'paymentProofMedia']));
    }

    public function show(SubscriptionPayment $subscriptionPayment): SubscriptionPaymentResource
    {
        return new SubscriptionPaymentResource($subscriptionPayment->load(['partnerSubscription', 'paymentProofMedia']));
    }

    public function update(SubscriptionPaymentRequest $request, SubscriptionPayment $subscriptionPayment): SubscriptionPaymentResource
    {
        $subscriptionPayment->update($request->validated());

        return new SubscriptionPaymentResource($subscriptionPayment->load(['partnerSubscription', 'paymentProofMedia']));
    }

    public function approve(Request $request, SubscriptionPayment $subscriptionPayment): SubscriptionPaymentResource
    {
        $this->authorizeManager($request);

        $subscriptionPayment->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        return new SubscriptionPaymentResource($subscriptionPayment->load(['partnerSubscription', 'paymentProofMedia']));
    }

    public function reject(Request $request, SubscriptionPayment $subscriptionPayment): SubscriptionPaymentResource
    {
        $this->authorizeManager($request);

        $subscriptionPayment->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
        ]);

        return new SubscriptionPaymentResource($subscriptionPayment->load(['partnerSubscription', 'paymentProofMedia']));
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'super_admin'], true), 403);
    }
}
