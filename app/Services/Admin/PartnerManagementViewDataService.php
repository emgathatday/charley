<?php

namespace App\Services\Admin;

use App\Models\PartnerSubscription;
use App\Models\User;
use App\Queries\Admin\PartnerManagementQuery;
use Illuminate\Database\Eloquent\Builder;

class PartnerManagementViewDataService
{
    public function __construct(private PartnerManagementQuery $users)
    {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function data(array $input = []): array
    {
        $filters = $this->users->filters($input);
        $usersQuery = $this->users->query($filters);
        $users = $usersQuery->latest('users.created_at')->paginate(10)->withQueryString();
        $users->getCollection()->each(fn (User $user) => $this->decorate($user));
        $stats = $this->users->stats();
        $subscriptionTierOptions = $this->users->subscriptionTierOptions();
        $tierStats = $this->users->tierStats();

        return [
            'users' => $users,
            'stats' => $stats,
            'filters' => $filters,
            'plantTypeOptions' => $this->users->plantTypeOptions(),
            'subscriptionTierOptions' => $subscriptionTierOptions,
            'tierStats' => $tierStats,
            'activeTab' => $filters['tab'],
            'activeTierId' => (string) $filters['subscription_tier_id'],
            'partnerTabBar' => $this->tabBar($filters, $stats, $tierStats),
            'partnerStatCards' => $this->statCards($users, $stats, $subscriptionTierOptions, $tierStats),
        ];
    }

    private function decorate(User $user): void
    {
        $this->decoratePartnerSubscription($user);

        $companyName = $user->partner_company_name ?: $this->displayName($user);
        $verificationStatus = (string) ($user->partner_approval_status ?: $user->status);
        $subscriptionStatus = (string) ($user->partner_subscription_status ?: $user->status);

        $user->display_name = $this->displayName($user);
        $user->plant_type_label = $user->partner_plant_type_name ?: 'No plant type';
        $user->partner_company_display_name = $companyName;
        $user->partner_company_meta = trim(($user->partner_website ?: $user->email).' - '.($user->partner_country ?: $user->plant_type_label), ' -');
        $user->partner_logo_seed = rawurlencode($companyName);
        $user->partner_tier_code = strtolower((string) ($user->partner_tier_code ?: 'inactive'));
        $user->partner_tier_label = $user->partner_tier_label ?: 'No active tier';
        $user->partner_verification_label = $user->partner_approval_status ? str_replace('_', ' ', ucfirst((string) $user->partner_approval_status)) : ($user->status === 'active' ? 'Verified' : $this->statusLabel($user));
        $user->partner_verification_class = match ($verificationStatus) {
            'approved', 'active' => 'status-active',
            'suspended', 'frozen', 'rejected' => 'status-suspended',
            default => 'status-pending',
        };
        $user->partner_subscription_label = match ($subscriptionStatus) {
            'active' => 'Active',
            'pending_approval' => 'Pending approval',
            'suspended', 'frozen' => 'Frozen',
            'inactive' => 'Not activated',
            default => ucfirst(str_replace('_', ' ', (string) ($user->partner_subscription_status ?: 'Not activated'))),
        };
        $user->partner_subscription_class = match ($subscriptionStatus) {
            'active' => 'status-active',
            'suspended', 'frozen', 'cancelled', 'rejected', 'expired' => 'status-suspended',
            default => 'status-pending',
        };
        $user->partner_is_review = $user->partner_verification_class === 'status-pending';
    }

    private function decoratePartnerSubscription(User $user): void
    {
        $subscription = PartnerSubscription::query()
            ->with('tier')
            ->active()
            ->where('user_id', $user->id)
            ->when($user->partner_active_subscription_id ?? null, fn (Builder $query, int $subscriptionId) => $query->orderByRaw('case when id = ? then 0 else 1 end', [$subscriptionId]))
            ->latest('starts_at')
            ->latest('id')
            ->first();

        $user->partner_active_subscription_id = $subscription?->id ?? ($user->partner_active_subscription_id ?? null);
        $user->partner_tier_label = $subscription?->tier?->display_name ?? 'No active tier';
        $user->partner_tier_code = $subscription?->tier?->code ?? 'inactive';
        $user->partner_subscription_status = $subscription?->status ?? ($user->partner_subscription_status ?? 'inactive');
        $user->partner_subscription_expires_at = $subscription?->ends_at ?? ($user->partner_subscription_expires_at ?? null);
    }

    private function tabBar(array $filters, array $stats, array $tierStats): array
    {
        $activeTab = $filters['tab'] ?? 'all';
        $activeTierId = (string) ($filters['subscription_tier_id'] ?? '');
        $suspendedCount = ($stats['suspended_users'] ?? 0) + ($stats['frozen_users'] ?? 0);

        $tabs = array_merge([
            ['label' => 'All Partners', 'count' => $stats['total_users'] ?? 0, 'active' => $activeTab === 'all' && $activeTierId === '', 'href' => $this->routeFor($filters, ['tab' => 'all', 'subscription_tier_id' => ''])],
            ['label' => 'Pending Approval', 'count' => $stats['pending_approvals'] ?? 0, 'active' => $activeTab === 'pending', 'href' => $this->routeFor($filters, ['tab' => 'pending', 'subscription_tier_id' => ''])],
        ], collect($tierStats)->map(fn (array $tier, string|int $tierId) => [
            'label' => $tier['label'],
            'count' => $tier['count'],
            'active' => $activeTierId === (string) $tierId,
            'href' => $this->routeFor($filters, ['tab' => 'all', 'subscription_tier_id' => $tierId]),
        ])->values()->all(), [
            ['label' => 'Suspended', 'count' => $suspendedCount, 'active' => $activeTab === 'suspended', 'href' => $this->routeFor($filters, ['tab' => 'suspended', 'subscription_tier_id' => ''])],
        ]);

        return ['tabs' => $tabs];
    }

    private function statCards($users, array $stats, array $subscriptionTierOptions, array $tierStats): array
    {
        $suspendedCount = ($stats['suspended_users'] ?? 0) + ($stats['frozen_users'] ?? 0);
        $activeSubscriptionCount = collect($tierStats)->sum('count');

        return [
            ['icon' => 'icon-partners', 'value' => number_format($stats['total_users'] ?? $users->total()), 'label' => 'Total Partners', 'trend' => number_format($users->count()).' this page'],
            ['icon' => 'icon-month', 'value' => number_format($activeSubscriptionCount), 'label' => 'Active Subscriptions', 'trend' => 'Module 04 source'],
            ['icon' => 'icon-clock', 'value' => number_format($stats['pending_approvals'] ?? 0), 'label' => 'Pending Approval', 'trend' => 'Awaiting review'],
            ['icon' => 'icon-lock', 'value' => number_format($suspendedCount), 'label' => 'Restricted', 'trend' => 'Frozen or suspended'],
            ['icon' => 'icon-billing', 'value' => number_format(count($subscriptionTierOptions)), 'label' => 'Active Tiers', 'trend' => 'Dynamic catalog'],
        ];
    }

    private function routeFor(array $filters, array $overrides): string
    {
        return route('admin.dashboard.iam.users.partners', array_merge([
            'tab' => $filters['tab'] ?? 'all',
            'keyword' => $filters['keyword'] ?? '',
            'plant_type_id' => $filters['plant_type_id'] ?? '',
            'subscription_tier_id' => $filters['subscription_tier_id'] ?? '',
        ], $overrides));
    }

    private function displayName(User $user): string
    {
        $name = trim(implode(' ', array_filter([$user->first_name, $user->last_name])));

        return $name !== '' ? $name : ($user->username ?: $user->email);
    }

    private function statusLabel(User $user): string
    {
        return match ($user->status) {
            'active' => 'Active',
            'frozen' => 'Frozen',
            'suspended' => 'Suspended',
            default => ucfirst((string) $user->status),
        };
    }

}
