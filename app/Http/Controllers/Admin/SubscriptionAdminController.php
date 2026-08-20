<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberSubscriptionPlan;
use App\Models\SubscriptionPermission;
use App\Models\SubscriptionTier;
use App\Services\Admin\SubscriptionAdminDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionAdminController extends Controller
{
    public function __construct(private readonly SubscriptionAdminDataService $subscriptionAdminData)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.subscriptions.index', $this->subscriptionAdminData->indexViewData(
            $request->only(['keyword', 'status', 'visibility', 'billing_cycle', 'subscription_status', 'payment_status', 'quota_period'])
        ));
    }

    public function createTier(): View
    {
        return view('admin.subscriptions.tiers.create', $this->subscriptionAdminData->createTierViewData());
    }

    public function storeTier(Request $request): RedirectResponse
    {
        $permissions = $this->subscriptionAdminData->activeSubscriptionPermissions();
        $this->subscriptionAdminData->createTier(
            $this->validatedTier($request),
            $this->validatedTierPermissions($request, $permissions)
        );

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Subscription tier created.');
    }

    public function editTier(string $subscriptionTier): View
    {
        return view('admin.subscriptions.tiers.edit', $this->subscriptionAdminData->editTierViewData($subscriptionTier));
    }

    public function updateTier(Request $request, string $subscriptionTier): RedirectResponse
    {
        $tier = SubscriptionTier::findOrFail($subscriptionTier);
        $permissions = $this->subscriptionAdminData->activeSubscriptionPermissions();
        $this->subscriptionAdminData->updateTier(
            $subscriptionTier,
            $this->validatedTier($request, $tier),
            $this->validatedTierPermissions($request, $permissions)
        );

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Subscription tier updated.');
    }

    public function createMemberPlan(): View
    {
        return view('admin.subscriptions.member-plans.create');
    }

    public function storeMemberPlan(Request $request): RedirectResponse
    {
        $this->subscriptionAdminData->createMemberPlan($this->validatedMemberPlan($request));

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Member plan created.');
    }

    public function editMemberPlan(string $memberSubscriptionPlan): View
    {
        return view('admin.subscriptions.member-plans.edit', $this->subscriptionAdminData->editMemberPlanViewData($memberSubscriptionPlan));
    }

    public function updateMemberPlan(Request $request, string $memberSubscriptionPlan): RedirectResponse
    {
        $plan = MemberSubscriptionPlan::findOrFail($memberSubscriptionPlan);
        $this->subscriptionAdminData->updateMemberPlan($memberSubscriptionPlan, $this->validatedMemberPlan($request, $plan));

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Member plan updated.');
    }

    public function approvePartnerSubscription(string $partnerSubscription): RedirectResponse
    {
        $this->subscriptionAdminData->approvePartnerSubscription($partnerSubscription, auth()->id());

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Partner subscription approved.');
    }

    public function cancelPartnerSubscription(string $partnerSubscription): RedirectResponse
    {
        $this->subscriptionAdminData->cancelPartnerSubscription($partnerSubscription);

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Partner subscription cancelled.');
    }

    public function approvePayment(string $subscriptionPayment): RedirectResponse
    {
        $this->subscriptionAdminData->approvePayment($subscriptionPayment, auth()->id());

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Payment approved.');
    }

    public function rejectPayment(string $subscriptionPayment): RedirectResponse
    {
        $this->subscriptionAdminData->rejectPayment($subscriptionPayment, auth()->id());

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Payment rejected.');
    }

    private function validatedTier(Request $request, ?SubscriptionTier $tier = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('subscription_tiers', 'code')->ignore($tier?->id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('subscription_tiers', 'name')->ignore($tier?->id)],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly', 'custom'])],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_public' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function validatedMemberPlan(Request $request, ?MemberSubscriptionPlan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('member_subscription_plans', 'name')->ignore($plan?->id)],
            'display_name' => ['required', 'string', 'max:255'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'ai_monthly_limit' => ['required', 'integer', 'min:-1'],
            'features' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['features'] = filled($validated['features'] ?? null)
            ? array_map('trim', explode(',', $validated['features']))
            : null;

        return $validated;
    }

    private function validatedTierPermissions(Request $request, $permissions): array
    {
        $rules = ['permissions' => ['nullable', 'array']];

        foreach ($permissions as $permission) {
            $rules["permissions.{$permission->id}.enabled"] = ['nullable', 'boolean'];
            $rules["permissions.{$permission->id}.value"] = match ($permission->value_type) {
                'integer' => ['nullable', 'integer'],
                'json' => ['nullable', 'json'],
                default => ['nullable', 'string'],
            };
        }

        return $request->validate($rules)['permissions'] ?? [];
    }
}
