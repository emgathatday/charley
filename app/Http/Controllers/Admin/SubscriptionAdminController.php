<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementQuota;
use App\Models\MemberSubscriptionPlan;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionAdminController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.subscriptions.index', [
            'availableTables' => $this->availableTables(),
            'filters' => $request->only(['subscription_status', 'payment_status', 'quota_period']),
            'tiers' => $this->hasTable('subscription_tiers') ? SubscriptionTier::query()->orderBy('monthly_price')->get() : collect(),
            'memberPlans' => $this->hasTable('member_subscription_plans') ? MemberSubscriptionPlan::query()->orderBy('monthly_price')->get() : collect(),
            'partnerSubscriptions' => $this->hasTable('partner_subscriptions')
                ? PartnerSubscription::query()
                    ->with(['user', 'tier'])
                    ->when($request->filled('subscription_status'), fn ($query) => $query->where('status', $request->input('subscription_status')))
                    ->latest()
                    ->limit(25)
                    ->get()
                : collect(),
            'subscriptionPayments' => $this->hasTable('subscription_payments')
                ? SubscriptionPayment::query()
                    ->with(['partnerSubscription.user', 'paymentProofMedia'])
                    ->when($request->filled('payment_status'), fn ($query) => $query->where('status', $request->input('payment_status')))
                    ->latest()
                    ->limit(25)
                    ->get()
                : collect(),
            'announcementQuotas' => $this->hasTable('announcement_quotas')
                ? AnnouncementQuota::query()
                    ->with('user')
                    ->when($request->filled('quota_period'), fn ($query) => $query->where('period', $request->input('quota_period')))
                    ->latest()
                    ->limit(25)
                    ->get()
                : collect(),
            'stats' => [
                'pending_approvals' => $this->hasTable('partner_subscriptions') ? PartnerSubscription::where('status', 'pending_approval')->count() : 0,
                'active_partner_subscriptions' => $this->hasTable('partner_subscriptions') ? PartnerSubscription::where('status', 'active')->count() : 0,
                'pending_payments' => $this->hasTable('subscription_payments') ? SubscriptionPayment::where('status', 'pending')->count() : 0,
                'quota_periods' => $this->hasTable('announcement_quotas') ? AnnouncementQuota::distinct('period')->count('period') : 0,
            ],
        ]);
    }

    public function createTier(): View
    {
        return view('admin.subscriptions.tiers.create');
    }

    public function storeTier(Request $request): RedirectResponse
    {
        $this->abortIfMissingTable('subscription_tiers');
        SubscriptionTier::create($this->validatedTier($request));

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Subscription tier created.');
    }

    public function editTier(string $subscriptionTier): View
    {
        $this->abortIfMissingTable('subscription_tiers');

        return view('admin.subscriptions.tiers.edit', ['subscriptionTier' => SubscriptionTier::findOrFail($subscriptionTier)]);
    }

    public function updateTier(Request $request, string $subscriptionTier): RedirectResponse
    {
        $this->abortIfMissingTable('subscription_tiers');
        $tier = SubscriptionTier::findOrFail($subscriptionTier);
        $tier->update($this->validatedTier($request, $tier));

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Subscription tier updated.');
    }

    public function createMemberPlan(): View
    {
        return view('admin.subscriptions.member-plans.create');
    }

    public function storeMemberPlan(Request $request): RedirectResponse
    {
        $this->abortIfMissingTable('member_subscription_plans');
        MemberSubscriptionPlan::create($this->validatedMemberPlan($request));

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Member plan created.');
    }

    public function editMemberPlan(string $memberSubscriptionPlan): View
    {
        $this->abortIfMissingTable('member_subscription_plans');

        return view('admin.subscriptions.member-plans.edit', ['memberSubscriptionPlan' => MemberSubscriptionPlan::findOrFail($memberSubscriptionPlan)]);
    }

    public function updateMemberPlan(Request $request, string $memberSubscriptionPlan): RedirectResponse
    {
        $this->abortIfMissingTable('member_subscription_plans');
        $plan = MemberSubscriptionPlan::findOrFail($memberSubscriptionPlan);
        $plan->update($this->validatedMemberPlan($request, $plan));

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Member plan updated.');
    }

    public function approvePartnerSubscription(string $partnerSubscription): RedirectResponse
    {
        $this->abortIfMissingTable('partner_subscriptions');
        PartnerSubscription::findOrFail($partnerSubscription)->update([
            'status' => 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Partner subscription approved.');
    }

    public function cancelPartnerSubscription(string $partnerSubscription): RedirectResponse
    {
        $this->abortIfMissingTable('partner_subscriptions');
        PartnerSubscription::findOrFail($partnerSubscription)->update(['status' => 'cancelled']);

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Partner subscription cancelled.');
    }

    public function approvePayment(string $subscriptionPayment): RedirectResponse
    {
        $this->abortIfMissingTable('subscription_payments');
        SubscriptionPayment::findOrFail($subscriptionPayment)->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Payment approved.');
    }

    public function rejectPayment(string $subscriptionPayment): RedirectResponse
    {
        $this->abortIfMissingTable('subscription_payments');
        SubscriptionPayment::findOrFail($subscriptionPayment)->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Payment rejected.');
    }

    private function validatedTier(Request $request, ?SubscriptionTier $tier = null): array
    {
        return $request->validate([
            'name' => ['required', Rule::in(['gold', 'diamond', 'platinum']), Rule::unique('subscription_tiers', 'name')->ignore($tier?->id)],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'ai_monthly_limit' => ['required', 'integer', 'min:-1'],
            'announcement_frequency' => ['required', Rule::in(['weekly', 'monthly'])],
            'announcement_limit' => ['required', 'integer', 'min:0'],
            'can_host_webinar' => ['required', 'boolean'],
            'can_initiate_message' => ['required', 'boolean'],
            'can_create_poll' => ['required', 'boolean'],
            'can_publish_events' => ['required', 'boolean'],
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

    private function availableTables(): array
    {
        return [
            'subscription_tiers' => $this->hasTable('subscription_tiers'),
            'member_subscription_plans' => $this->hasTable('member_subscription_plans'),
            'partner_subscriptions' => $this->hasTable('partner_subscriptions'),
            'subscription_payments' => $this->hasTable('subscription_payments'),
            'announcement_quotas' => $this->hasTable('announcement_quotas'),
        ];
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function abortIfMissingTable(string $table): void
    {
        abort_unless($this->hasTable($table), 503, "Database table [{$table}] is not available.");
    }
}