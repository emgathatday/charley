<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerSubscriptionRequest;
use App\Http\Resources\PartnerSubscriptionResource;
use App\Models\PartnerSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PartnerSubscriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $subscriptions = PartnerSubscription::query()
            ->with(['tier', 'payments'])
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('tier_id'), fn ($query) => $query->where('tier_id', $request->integer('tier_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return PartnerSubscriptionResource::collection($subscriptions);
    }

    public function store(PartnerSubscriptionRequest $request): PartnerSubscriptionResource
    {
        $subscription = PartnerSubscription::create([
            ...$request->validated(),
            'user_id' => $request->integer('user_id') ?: $request->user()->id,
            'status' => $request->input('status', 'pending_approval'),
        ]);

        return new PartnerSubscriptionResource($subscription->load(['tier', 'payments']));
    }

    public function show(PartnerSubscription $partnerSubscription): PartnerSubscriptionResource
    {
        return new PartnerSubscriptionResource($partnerSubscription->load(['tier', 'payments']));
    }

    public function update(PartnerSubscriptionRequest $request, PartnerSubscription $partnerSubscription): PartnerSubscriptionResource
    {
        $partnerSubscription->update($request->validated());

        return new PartnerSubscriptionResource($partnerSubscription->load(['tier', 'payments']));
    }

    public function approve(Request $request, PartnerSubscription $partnerSubscription): PartnerSubscriptionResource
    {
        $this->authorizeManager($request);

        $partnerSubscription->update([
            'status' => 'active',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return new PartnerSubscriptionResource($partnerSubscription->load(['tier', 'payments']));
    }

    public function cancel(Request $request, PartnerSubscription $partnerSubscription): PartnerSubscriptionResource
    {
        abort_unless($this->canManage($request) || $partnerSubscription->user_id === $request->user()->id, 403);

        $partnerSubscription->update(['status' => 'cancelled']);

        return new PartnerSubscriptionResource($partnerSubscription->load(['tier', 'payments']));
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($this->canManage($request), 403);
    }

    private function canManage(Request $request): bool
    {
        return in_array($request->user()?->role, ['admin', 'super_admin'], true);
    }
}
