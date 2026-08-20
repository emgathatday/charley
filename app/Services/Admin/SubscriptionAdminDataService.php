<?php

namespace App\Services\Admin;

use App\Models\AnnouncementQuota;
use App\Models\MemberSubscriptionPlan;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPermission;
use App\Models\SubscriptionTier;
use App\Models\SubscriptionUsageCounter;
use App\Services\SubscriptionPermissionProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionAdminDataService
{
    public function __construct(private readonly SubscriptionPermissionProvider $permissionProvider)
    {
    }

    public function indexViewData(array $filters): array
    {
        $availableTables = $this->availableTables();
        $tiers = $availableTables['subscription_tiers']
            ? SubscriptionTier::query()->with(['tierPermissions.permission'])->withCount('partnerSubscriptions')->orderBy('sort_order')->orderBy('monthly_price')->get()
            : collect();
        $filteredTiers = $this->filterTiers($tiers, $filters);
        $totalPlans = $tiers->count();
        $activePlans = $tiers->where('is_active', true)->count();
        $publicPlans = $tiers->where('is_public', true)->count();
        $adminOnlyPlans = $totalPlans - $publicPlans;
        $activeAveragePrice = (float) ($tiers->where('is_active', true)->avg('monthly_price') ?? 0);
        $subscriptionPermissions = $availableTables['subscription_permissions'] ? $this->permissionProvider->all() : collect();

        return [
            'availableTables' => $availableTables,
            'filters' => $filters,
            'tiers' => $tiers,
            'filteredTiers' => $filteredTiers,
            'subscriptionPermissions' => $subscriptionPermissions,
            'memberPlans' => $availableTables['member_subscription_plans'] ? MemberSubscriptionPlan::query()->orderBy('monthly_price')->get() : collect(),
            'partnerSubscriptions' => $availableTables['partner_subscriptions'] ? PartnerSubscription::query()->with(['user', 'tier', 'payments'])->when($filters['subscription_status'] ?? null, fn ($query, $status) => $query->where('status', $status))->latest()->limit(25)->get() : collect(),
            'subscriptionPayments' => $availableTables['subscription_payments'] ? SubscriptionPayment::query()->with(['partnerSubscription.user', 'paymentProofMedia'])->when($filters['payment_status'] ?? null, fn ($query, $status) => $query->where('status', $status))->latest()->limit(25)->get() : collect(),
            'subscriptionUsageCounters' => $availableTables['subscription_usage_counters'] ? SubscriptionUsageCounter::query()->with(['partnerSubscription.user', 'permission'])->when($filters['quota_period'] ?? null, fn ($query, $period) => $query->where('period', $period))->latest()->limit(25)->get() : collect(),
            'announcementQuotas' => $availableTables['announcement_quotas'] ? AnnouncementQuota::query()->with('user')->when($filters['quota_period'] ?? null, fn ($query, $period) => $query->where('period', $period))->latest()->limit(25)->get() : collect(),
            'stats' => [
                'pending_approvals' => $availableTables['partner_subscriptions'] ? PartnerSubscription::where('status', 'pending_approval')->count() : 0,
                'active_partner_subscriptions' => $availableTables['partner_subscriptions'] ? PartnerSubscription::where('status', 'active')->count() : 0,
                'pending_payments' => $availableTables['subscription_payments'] ? SubscriptionPayment::where('status', 'pending')->count() : 0,
                'quota_periods' => $availableTables['subscription_usage_counters'] ? SubscriptionUsageCounter::distinct('period')->count('period') : 0,
            ],
            'subscriptionMetrics' => [
                'totalPlans' => $totalPlans,
                'activePlans' => $activePlans,
                'publicPlans' => $publicPlans,
                'adminOnlyPlans' => $adminOnlyPlans,
                'activeAveragePrice' => $activeAveragePrice,
                'showingTo' => $filteredTiers->count(),
            ],
            'subscriptionStatCards' => [
                ['class' => 'blue', 'icon' => 'billing', 'label' => 'Total Tiers', 'value' => number_format($totalPlans), 'sub' => number_format($publicPlans).' public / '.number_format($adminOnlyPlans).' admin-only'],
                ['class' => 'green', 'icon' => 'check', 'label' => 'Active Tiers', 'value' => number_format($activePlans), 'sub' => 'Available for subscription assignment'],
                ['class' => 'amber', 'icon' => 'calculator', 'label' => 'Avg Monthly Price', 'value' => '$'.number_format($activeAveragePrice, 0), 'sub' => 'Active tiers only'],
                ['class' => 'blue2', 'icon' => 'settings-2', 'label' => 'Permission Keys', 'value' => number_format($subscriptionPermissions->count()), 'sub' => 'boolean, integer, string, json'],
            ],
        ];
    }

    public function createTierViewData(): array
    {
        return $this->tierFormViewData([
            'subscriptionPermissions' => $this->activeSubscriptionPermissions(),
            'assignedTierPermissions' => collect(),
        ]);
    }

    public function editTierViewData(string $subscriptionTier): array
    {
        $this->abortIfMissingTable('subscription_tiers');
        $tier = SubscriptionTier::query()->with(['tierPermissions.permission'])->findOrFail($subscriptionTier);

        return $this->tierFormViewData([
            'subscriptionTier' => $tier,
            'subscriptionPermissions' => $this->activeSubscriptionPermissions(),
            'assignedTierPermissions' => $tier->tierPermissions->keyBy('permission_id'),
            'tierLabel' => $tier->display_name ?: $tier->name,
            'enabledPermissions' => $tier->tierPermissions->count(),
        ]);
    }

    public function editMemberPlanViewData(string $memberSubscriptionPlan): array
    {
        $this->abortIfMissingTable('member_subscription_plans');

        return ['memberSubscriptionPlan' => MemberSubscriptionPlan::findOrFail($memberSubscriptionPlan)];
    }

    public function createTier(array $attributes, array $permissionPayload): SubscriptionTier
    {
        $this->abortIfMissingTable('subscription_tiers');

        return DB::transaction(function () use ($attributes, $permissionPayload): SubscriptionTier {
            $tier = new SubscriptionTier();
            $tier->forceFill($attributes + $this->legacyTierDefaults())->save();
            $this->syncTierPermissions($tier, $permissionPayload);

            return $tier;
        });
    }

    public function updateTier(string $subscriptionTier, array $attributes, array $permissionPayload): SubscriptionTier
    {
        $this->abortIfMissingTable('subscription_tiers');

        return DB::transaction(function () use ($subscriptionTier, $attributes, $permissionPayload): SubscriptionTier {
            $tier = SubscriptionTier::findOrFail($subscriptionTier);
            $tier->forceFill($attributes + $this->legacyTierDefaults())->save();
            $this->syncTierPermissions($tier, $permissionPayload);

            return $tier;
        });
    }

    public function createMemberPlan(array $attributes): MemberSubscriptionPlan
    {
        $this->abortIfMissingTable('member_subscription_plans');

        return MemberSubscriptionPlan::create($attributes);
    }

    public function updateMemberPlan(string $memberSubscriptionPlan, array $attributes): MemberSubscriptionPlan
    {
        $this->abortIfMissingTable('member_subscription_plans');
        $plan = MemberSubscriptionPlan::findOrFail($memberSubscriptionPlan);
        $plan->update($attributes);

        return $plan;
    }

    public function approvePartnerSubscription(string $partnerSubscription, ?int $adminId): void
    {
        $this->abortIfMissingTable('partner_subscriptions');
        PartnerSubscription::findOrFail($partnerSubscription)->update(['status' => 'active', 'approved_by' => $adminId, 'approved_at' => now()]);
    }

    public function cancelPartnerSubscription(string $partnerSubscription): void
    {
        $this->abortIfMissingTable('partner_subscriptions');
        PartnerSubscription::findOrFail($partnerSubscription)->update(['status' => 'cancelled']);
    }

    public function approvePayment(string $subscriptionPayment, ?int $adminId): void
    {
        $this->updatePaymentStatus($subscriptionPayment, 'approved', $adminId);
    }

    public function rejectPayment(string $subscriptionPayment, ?int $adminId): void
    {
        $this->updatePaymentStatus($subscriptionPayment, 'rejected', $adminId);
    }

    public function activeSubscriptionPermissions(): Collection
    {
        if (! $this->hasTable('subscription_permissions')) {
            return collect();
        }

        return $this->permissionProvider->active();
    }

    public function availableTables(): array
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

    public function abortIfMissingTable(string $table): void
    {
        abort_unless($this->hasTable($table), 503, "Database table [{$table}] is not available.");
    }

    private function tierFormViewData(array $data): array
    {
        return $data + [
            'tierFormFields' => [
                ['type' => 'text', 'name' => 'code', 'label' => 'Code', 'placeholder' => 'partner_plus', 'required' => true],
                ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'placeholder' => 'Partner Plus', 'required' => true],
                ['type' => 'text', 'name' => 'display_name', 'label' => 'Display name', 'placeholder' => 'Partner Plus Membership', 'required' => true],
                ['type' => 'number', 'name' => 'monthly_price', 'label' => 'Monthly price', 'required' => true, 'attributes' => ['min' => '0', 'step' => '0.01']],
                ['type' => 'select', 'name' => 'billing_cycle', 'label' => 'Billing cycle', 'required' => true, 'options' => ['monthly' => 'Monthly', 'yearly' => 'Yearly', 'custom' => 'Custom']],
                ['type' => 'number', 'name' => 'duration_days', 'label' => 'Duration days', 'placeholder' => 'Auto', 'hint' => 'Leave blank when the billing cycle determines the duration.', 'attributes' => ['min' => '1']],
                ['type' => 'number', 'name' => 'sort_order', 'label' => 'Display order', 'required' => true, 'attributes' => ['min' => '0']],
            ],
        ];
    }

    private function filterTiers(Collection $tiers, array $filters): Collection
    {
        return $tiers
            ->when($filters['keyword'] ?? null, function (Collection $collection, string $keyword): Collection {
                $needle = Str::lower($keyword);

                return $collection->filter(function (SubscriptionTier $tier) use ($needle): bool {
                    $label = $tier->display_name ?: $tier->name;

                    return Str::contains(Str::lower($label.' '.$tier->code.' '.$tier->name.' '.$tier->description), $needle);
                });
            })
            ->when(($filters['status'] ?? null) === 'active', fn (Collection $collection): Collection => $collection->filter(fn (SubscriptionTier $tier): bool => (bool) $tier->is_active))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Collection $collection): Collection => $collection->filter(fn (SubscriptionTier $tier): bool => ! (bool) $tier->is_active))
            ->when(($filters['visibility'] ?? null) === 'public', fn (Collection $collection): Collection => $collection->filter(fn (SubscriptionTier $tier): bool => (bool) $tier->is_public))
            ->when(($filters['visibility'] ?? null) === 'admin_only', fn (Collection $collection): Collection => $collection->filter(fn (SubscriptionTier $tier): bool => ! (bool) $tier->is_public))
            ->when($filters['billing_cycle'] ?? null, fn (Collection $collection, string $cycle): Collection => $collection->filter(fn (SubscriptionTier $tier): bool => $tier->billing_cycle === $cycle));
    }

    private function syncTierPermissions(SubscriptionTier $tier, array $payload): void
    {
        $this->abortIfMissingTable('subscription_tier_permissions');
        $permissions = $this->activeSubscriptionPermissions();
        $enabledIds = [];

        foreach ($permissions as $permission) {
            $input = $payload[$permission->id] ?? [];

            if (! (bool) ($input['enabled'] ?? false)) {
                continue;
            }

            $enabledIds[] = $permission->id;
            $tier->tierPermissions()->updateOrCreate(['permission_id' => $permission->id], ['value' => $this->castTierPermissionValue($permission, $input['value'] ?? null)]);
        }

        if ($enabledIds === []) {
            $tier->tierPermissions()->delete();

            return;
        }

        $tier->tierPermissions()->whereNotIn('permission_id', $enabledIds)->delete();
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
                throw ValidationException::withMessages(["permissions.{$permission->id}.value" => 'Permission value must be valid JSON.']);
            }

            return $decoded;
        }

        return $value ?? (is_scalar($permission->default_value) ? $permission->default_value : '');
    }

    private function legacyTierDefaults(): array
    {
        return [
            'ai_monthly_limit' => 0,
            'announcement_frequency' => 'monthly',
            'announcement_limit' => 0,
            'can_host_webinar' => false,
            'can_initiate_message' => false,
            'can_create_poll' => false,
            'can_publish_events' => false,
        ];
    }

    private function updatePaymentStatus(string $subscriptionPayment, string $status, ?int $adminId): void
    {
        $this->abortIfMissingTable('subscription_payments');
        SubscriptionPayment::findOrFail($subscriptionPayment)->update(['status' => $status, 'approved_by' => $adminId]);
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
