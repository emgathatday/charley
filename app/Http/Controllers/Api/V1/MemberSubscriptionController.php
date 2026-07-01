<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberSubscriptionRequest;
use App\Http\Resources\MemberSubscriptionResource;
use App\Models\MemberSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberSubscriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $subscriptions = MemberSubscription::query()
            ->with('plan')
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('plan_id'), fn ($query) => $query->where('plan_id', $request->integer('plan_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return MemberSubscriptionResource::collection($subscriptions);
    }

    public function store(MemberSubscriptionRequest $request): MemberSubscriptionResource
    {
        $subscription = MemberSubscription::create([
            ...$request->validated(),
            'user_id' => $request->integer('user_id') ?: $request->user()->id,
            'status' => $request->input('status', 'active'),
            'payment_method' => $request->input('payment_method', 'bank_transfer'),
        ]);

        return new MemberSubscriptionResource($subscription->load('plan'));
    }

    public function show(MemberSubscription $memberSubscription): MemberSubscriptionResource
    {
        return new MemberSubscriptionResource($memberSubscription->load('plan'));
    }

    public function update(MemberSubscriptionRequest $request, MemberSubscription $memberSubscription): MemberSubscriptionResource
    {
        $memberSubscription->update($request->validated());

        return new MemberSubscriptionResource($memberSubscription->load('plan'));
    }

    public function cancel(Request $request, MemberSubscription $memberSubscription): MemberSubscriptionResource
    {
        abort_unless($this->canManage($request) || $memberSubscription->user_id === $request->user()->id, 403);

        $memberSubscription->update(['status' => 'cancelled']);

        return new MemberSubscriptionResource($memberSubscription->load('plan'));
    }

    private function canManage(Request $request): bool
    {
        return in_array($request->user()?->role, ['admin', 'super_admin'], true);
    }
}
