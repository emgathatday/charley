<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementQuota;
use App\Models\MemberSubscriptionPlan;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPermission;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionTier;
use App\Models\SubscriptionUsageCounter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionAdminController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.subscriptions.index', [
            'availableTables' => $this->availableTables(),
            'filters' => $request->only(['subscription_status', 'payment_status', 'quota_period']),
            'tiers' => $this->hasTable('subscription_tiers')
                ? SubscriptionTier::query()
                    ->with(['tierPermissions.permission'])
                    ->withCount('partnerSubscriptions')
                    ->orderBy('sort_order')
                    ->orderBy('monthly_price')
                    ->get()
                : collect(),
            'subscriptionPermissions' => $this->hasTable('subscription_permissions')
                ? SubscriptionPermission::query()
                    ->orderBy('module')
                    ->orderBy('key')
                    ->get()
                : collect(),
            'memberPlans' => $this->hasTable('member_subscription_plans') ? MemberSubscriptionPlan::query()->orderBy('monthly_price')->get() : collect(),
            'partnerSubscriptions' => $this->hasTable('partner_subscriptions')
                ? PartnerSubscription::query()
                    ->with(['user', 'tier', 'payments'])
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
            'subscriptionUsageCounters' => $this->hasTable('subscription_usage_counters')
                ? SubscriptionUsageCounter::query()
                    ->with(['partnerSubscription.user', 'permission'])
                    ->when($request->filled('quota_period'), fn ($query) => $query->where('period', $request->input('quota_period')))
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
                'quota_periods' => $this->hasTable('subscription_usage_counters') ? SubscriptionUsageCounter::distinct('period')->count('period') : 0,
            ],
        ]);
    }

    public function createTier(): View
    {
        return view('admin.subscriptions.tiers.create', [
            'subscriptionPermissions' => $this->activeSubscriptionPermissions(),
            'assignedTierPermissions' => collect(),
        ]);
    }

    public function storeTier(Request $request): RedirectResponse
    {
        $this->abortIfMissingTable('subscription_tiers');
        $tier = SubscriptionTier::create($this->validatedTier($request));
        $this->syncTierPermissions($tier, $request);

        return redirect()->route('admin.dashboard.subscriptions.index')->with('status', 'Subscription tier created.');
    }

    public function editTier(string $subscriptionTier): View
    {
        $this->abortIfMissingTable('subscription_tiers');

        $tier = SubscriptionTier::query()
            ->with(['tierPermissions.permission'])
            ->findOrFail($subscriptionTier);

        return view('admin.subscriptions.tiers.edit', [
            'subscriptionTier' => $tier,
            'subscriptionPermissions' => $this->activeSubscriptionPermissions(),
            'assignedTierPermissions' => $tier->tierPermissions->keyBy('permission_id'),
        ]);
    }

    public function updateTier(Request $request, string $subscriptionTier): RedirectResponse
    {
        $this->abortIfMissingTable('subscription_tiers');
        $tier = SubscriptionTier::findOrFail($subscriptionTier);
        $tier->update($this->validatedTier($request, $tier));
        $this->syncTierPermissions($tier, $request);

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

    private function activeSubscriptionPermissions()
    {
        if (! $this->hasTable('subscription_permissions')) {
            return collect();
        }

        return SubscriptionPermission::query()
            ->active()
            ->orderBy('module')
            ->orderBy('name')
            ->get();
    }

    private function syncTierPermissions(SubscriptionTier $tier, Request $request): void
    {
        $this->abortIfMissingTable('subscription_tier_permissions');

        $permissions = $this->activeSubscriptionPermissions();
        $payload = $this->validatedTierPermissions($request, $permissions);
        $enabledIds = [];

        foreach ($permissions as $permission) {
            $input = $payload[$permission->id] ?? [];

            if (! (bool) ($input['enabled'] ?? false)) {
                continue;
            }

            $enabledIds[] = $permission->id;

            $tier->tierPermissions()->updateOrCreate(
                ['permission_id' => $permission->id],
                ['value' => $this->castTierPermissionValue($permission, $input['value'] ?? null)]
            );
        }

        if ($enabledIds === []) {
            $tier->tierPermissions()->delete();
            return;
        }

        $tier->tierPermissions()
            ->whereNotIn('permission_id', $enabledIds)
            ->delete();
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

    private function castTierPermissionValue(SubscriptionPermission $permission, mixed $value): mixed
    {
        if ($permission->value_type === 'boolean') {
            return true;
        }

        if ($permission->value_type === 'integer') {
            return $value === null || $value === '' ? (int) ($permission->default_value ?? 0) : (int) $value;
        }

        if ($permission->value_type === 'json') {
            if ($value === null || $value === '') {
                return $permission->default_value ?? [];
            }

            $decoded = json_decode((string) $value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw ValidationException::withMessages([
                    "permissions.{$permission->id}.value" => 'Permission value must be valid JSON.',
                ]);
            }

            return $decoded;
        }

        return $value ?? (is_scalar($permission->default_value) ? $permission->default_value : '');
    }

    private function availableTables(): array
    {
        return [
            'subscription_tiers' => $this->hasTable('subscription_tiers'),
            'subscription_permissions' => $this->hasTable('subscription_permissions'),
            'subscription_tier_permissions' => $this->hasTable('subscription_tier_permissions'),
            'member_subscription_plans' => $this->hasTable('member_subscription_plans'),
            'partner_subscriptions' => $this->hasTable('partner_subscriptions'),
            'subscription_payments' => $this->hasTable('subscription_payments'),
            'subscription_usage_counters' => $this->hasTable('subscription_usage_counters'),
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