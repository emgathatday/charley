<?php

namespace Database\Seeders;

use App\Models\MemberSubscriptionPlan;
use App\Models\MemberSubscription;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPermission;
use App\Models\SubscriptionTier;
use App\Models\SubscriptionTierPermission;
use App\Models\SubscriptionUsageCounter;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = $this->seedTiers();
        $permissions = $this->seedPermissions();
        $this->seedTierPermissions($tiers, $permissions);
        $this->seedDemoSubscriptions($tiers, $permissions);
    }

    private function seedTiers(): array
    {
        $definitions = [
            'gold' => [
                'name' => 'gold',
                'display_name' => 'Gold Partner',
                'description' => 'Standard partner tier for vendors, suppliers, and service providers.',
                'monthly_price' => 199.00,
                'billing_cycle' => 'monthly',
                'duration_days' => null,
                'sort_order' => 10,
                'is_public' => true,
                'is_active' => true,
            ],
            'diamond' => [
                'name' => 'diamond',
                'display_name' => 'Diamond Partner',
                'description' => 'Premium tier for licensors and strategic technology partners.',
                'monthly_price' => 499.00,
                'billing_cycle' => 'monthly',
                'duration_days' => null,
                'sort_order' => 20,
                'is_public' => true,
                'is_active' => true,
            ],
            'platinum' => [
                'name' => 'platinum',
                'display_name' => 'Platinum Partner',
                'description' => 'Enterprise tier for manufacturing plants and operating partners.',
                'monthly_price' => 999.00,
                'billing_cycle' => 'monthly',
                'duration_days' => null,
                'sort_order' => 30,
                'is_public' => true,
                'is_active' => true,
            ],
        ];

        $tiers = [];

        foreach ($definitions as $code => $definition) {
            $tier = SubscriptionTier::query()->firstOrCreate(['code' => $code], $definition + ['code' => $code]);
            $tier->fill($definition)->save();
            $tiers[$code] = $tier;
        }

        return $tiers;
    }

    private function seedPermissions(): array
    {
        $definitions = [
            'announcements.create' => ['name' => 'Create announcements', 'module' => 'posts', 'value_type' => 'integer', 'default_value' => 0, 'description' => 'Monthly announcement quota.'],
            'events.publish' => ['name' => 'Publish events', 'module' => 'events', 'value_type' => 'boolean', 'default_value' => false, 'description' => 'Publish partner events.'],
            'webinars.host' => ['name' => 'Host webinars', 'module' => 'events', 'value_type' => 'boolean', 'default_value' => false, 'description' => 'Host webinars on the platform.'],
            'polls.create' => ['name' => 'Create polls', 'module' => 'polls', 'value_type' => 'boolean', 'default_value' => false, 'description' => 'Create technical polls.'],
            'messages.initiate' => ['name' => 'Initiate messages', 'module' => 'messaging', 'value_type' => 'boolean', 'default_value' => false, 'description' => 'Start messages with professionals.'],
            'jobs.create' => ['name' => 'Create jobs', 'module' => 'jobs', 'value_type' => 'integer', 'default_value' => 0, 'description' => 'Monthly job posting quota.'],
            'ai.use' => ['name' => 'Use AI assistant', 'module' => 'ai-assistant', 'value_type' => 'integer', 'default_value' => 0, 'description' => 'Monthly AI usage quota.'],
        ];

        $permissions = [];

        foreach ($definitions as $key => $definition) {
            $permission = SubscriptionPermission::query()->firstOrCreate(['key' => $key], $definition + ['key' => $key, 'is_active' => true]);
            $permission->fill($definition + ['is_active' => true])->save();
            $permissions[$key] = $permission;
        }

        return $permissions;
    }

    private function seedTierPermissions(array $tiers, array $permissions): void
    {
        $matrix = [
            'gold' => [
                'announcements.create' => 4,
                'events.publish' => false,
                'webinars.host' => false,
                'polls.create' => false,
                'messages.initiate' => false,
                'jobs.create' => 2,
                'ai.use' => 500,
            ],
            'diamond' => [
                'announcements.create' => 12,
                'events.publish' => true,
                'webinars.host' => true,
                'polls.create' => true,
                'messages.initiate' => true,
                'jobs.create' => 6,
                'ai.use' => 2000,
            ],
            'platinum' => [
                'announcements.create' => 8,
                'events.publish' => true,
                'webinars.host' => false,
                'polls.create' => false,
                'messages.initiate' => false,
                'jobs.create' => 8,
                'ai.use' => -1,
            ],
        ];

        foreach ($matrix as $tierCode => $values) {
            foreach ($values as $permissionKey => $value) {
                $tierPermission = SubscriptionTierPermission::query()->firstOrCreate(
                    ['tier_id' => $tiers[$tierCode]->id, 'permission_id' => $permissions[$permissionKey]->id],
                    ['value' => $value]
                );
                $tierPermission->fill(['value' => $value])->save();
            }
        }
    }

    private function seedDemoSubscriptions(array $tiers, array $permissions): void
    {
        $user = User::query()->orderBy('id')->first();

        $plan = MemberSubscriptionPlan::query()->firstOrCreate(
            ['name' => 'professional-ai-unlimited'],
            [
                'display_name' => 'Professional AI Unlimited',
                'monthly_price' => 49.00,
                'ai_monthly_limit' => -1,
                'features' => ['ai_unlimited', 'priority_support'],
                'is_active' => true,
            ]
        );

        if (! $user) {
            return;
        }

        $subscription = PartnerSubscription::query()->firstOrCreate(
            ['user_id' => $user->id, 'tier_id' => $tiers['gold']->id, 'starts_at' => now()->startOfMonth()],
            [
                'status' => 'active',
                'auto_renew' => false,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'ends_at' => now()->startOfMonth()->addMonth(),
            ]
        );

        SubscriptionPayment::query()->firstOrCreate(
            ['partner_subscription_id' => $subscription->id, 'period_start' => now()->startOfMonth()->toDateString()],
            [
                'amount' => $tiers['gold']->monthly_price,
                'payment_method' => 'bank_transfer',
                'period_end' => now()->endOfMonth()->toDateString(),
                'status' => 'approved',
                'transaction_code' => 'DEMO-SUB-GOLD',
                'approved_by' => $user->id,
            ]
        );

        MemberSubscription::query()->firstOrCreate(
            ['user_id' => $user->id, 'plan_id' => $plan->id, 'starts_at' => now()->startOfMonth()],
            [
                'status' => 'active',
                'ends_at' => now()->startOfMonth()->addMonth(),
                'payment_method' => 'bank_transfer',
            ]
        );

        SubscriptionUsageCounter::query()->firstOrCreate(
            [
                'partner_subscription_id' => $subscription->id,
                'permission_id' => $permissions['announcements.create']->id,
                'period' => now()->format('Y-m'),
            ],
            [
                'used_count' => 0,
                'quota_limit' => 4,
                'reset_at' => now()->endOfMonth(),
            ]
        );
    }
}
