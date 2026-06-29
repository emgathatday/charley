<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberSubscriptionPlanRequest;
use App\Http\Resources\MemberSubscriptionPlanResource;
use App\Models\MemberSubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberSubscriptionPlanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $plans = MemberSubscriptionPlan::query()
            ->when($request->has('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderBy('monthly_price')
            ->orderBy('display_name')
            ->paginate($request->integer('per_page', 15));

        return MemberSubscriptionPlanResource::collection($plans);
    }

    public function store(MemberSubscriptionPlanRequest $request): MemberSubscriptionPlanResource
    {
        $plan = MemberSubscriptionPlan::create($request->validated());

        return new MemberSubscriptionPlanResource($plan);
    }

    public function show(MemberSubscriptionPlan $memberSubscriptionPlan): MemberSubscriptionPlanResource
    {
        return new MemberSubscriptionPlanResource($memberSubscriptionPlan);
    }

    public function update(MemberSubscriptionPlanRequest $request, MemberSubscriptionPlan $memberSubscriptionPlan): MemberSubscriptionPlanResource
    {
        $memberSubscriptionPlan->update($request->validated());

        return new MemberSubscriptionPlanResource($memberSubscriptionPlan);
    }

    public function destroy(MemberSubscriptionPlan $memberSubscriptionPlan): MemberSubscriptionPlanResource
    {
        $memberSubscriptionPlan->delete();

        return new MemberSubscriptionPlanResource($memberSubscriptionPlan);
    }
}
