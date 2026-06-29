<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionTierRequest;
use App\Http\Resources\SubscriptionTierResource;
use App\Models\SubscriptionTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionTierController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tiers = SubscriptionTier::query()
            ->when($request->has('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderBy('monthly_price')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return SubscriptionTierResource::collection($tiers);
    }

    public function store(SubscriptionTierRequest $request): SubscriptionTierResource
    {
        $tier = SubscriptionTier::create($request->validated());

        return new SubscriptionTierResource($tier);
    }

    public function show(SubscriptionTier $subscriptionTier): SubscriptionTierResource
    {
        return new SubscriptionTierResource($subscriptionTier);
    }

    public function update(SubscriptionTierRequest $request, SubscriptionTier $subscriptionTier): SubscriptionTierResource
    {
        $subscriptionTier->update($request->validated());

        return new SubscriptionTierResource($subscriptionTier);
    }

    public function destroy(SubscriptionTier $subscriptionTier): SubscriptionTierResource
    {
        $subscriptionTier->delete();

        return new SubscriptionTierResource($subscriptionTier);
    }
}
