<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();

        $tierModel = new class extends Model
        {
            protected $table = 'subscription_tiers';
            public $timestamps = false;
            protected $guarded = [];
        };

        $memberPlanModel = new class extends Model
        {
            protected $table = 'member_subscription_plans';
            protected $guarded = [];
            protected $casts = ['features' => 'array'];
        };

        $partnerSubscriptionModel = new class extends Model
        {
            protected $table = 'partner_subscriptions';
            protected $guarded = [];
            protected $casts = ['approved_at' => 'datetime', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
        };

        $paymentModel = new class extends Model
        {
            protected $table = 'subscription_payments';
            protected $guarded = [];
            protected $casts = ['period_start' => 'date', 'period_end' => 'date'];
        };

        $memberSubscriptionModel = new class extends Model
        {
            protected $table = 'member_subscriptions';
            protected $guarded = [];
            protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
        };

        $quotaModel = new class extends Model
        {
            protected $table = 'announcement_quotas';
            protected $guarded = [];
        };

        $tiers = [
            ['name' => 'gold', 'monthly_price' => 199.00, 'ai_monthly_limit' => 500, 'announcement_frequency' => 'monthly', 'announcement_limit' => 4, 'can_host_webinar' => false, 'can_initiate_message' => true, 'can_create_poll' => false, 'can_publish_events' => false, 'is_active' => true],
            ['name' => 'diamond', 'monthly_price' => 499.00, 'ai_monthly_limit' => 2000, 'announcement_frequency' => 'weekly', 'announcement_limit' => 12, 'can_host_webinar' => true, 'can_initiate_message' => true, 'can_create_poll' => true, 'can_publish_events' => false, 'is_active' => true],
            ['name' => 'platinum', 'monthly_price' => 999.00, 'ai_monthly_limit' => -1, 'announcement_frequency' => 'weekly', 'announcement_limit' => 24, 'can_host_webinar' => true, 'can_initiate_message' => true, 'can_create_poll' => true, 'can_publish_events' => true, 'is_active' => true],
        ];

        foreach ($tiers as $tier) {
            $tierModel->newQuery()->firstOrCreate(['name' => $tier['name']], $tier);
        }

        $plan = $memberPlanModel->newQuery()->firstOrCreate(
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

        $goldTier = $tierModel->newQuery()->where('name', 'gold')->first();

        if ($goldTier) {
            $subscription = $partnerSubscriptionModel->newQuery()->firstOrCreate(
                ['user_id' => $user->id, 'tier_id' => $goldTier->id, 'starts_at' => now()->startOfMonth()],
                [
                    'status' => 'active',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'ends_at' => now()->startOfMonth()->addMonth(),
                ]
            );

            $paymentModel->newQuery()->firstOrCreate(
                ['partner_subscription_id' => $subscription->id, 'period_start' => now()->startOfMonth()->toDateString()],
                [
                    'amount' => $goldTier->monthly_price,
                    'payment_method' => 'bank_transfer',
                    'period_end' => now()->endOfMonth()->toDateString(),
                    'status' => 'approved',
                    'transaction_code' => 'DEMO-SUB-GOLD',
                    'approved_by' => $user->id,
                ]
            );
        }

        $memberSubscriptionModel->newQuery()->firstOrCreate(
            ['user_id' => $user->id, 'plan_id' => $plan->id, 'starts_at' => now()->startOfMonth()],
            [
                'status' => 'active',
                'ends_at' => now()->startOfMonth()->addMonth(),
                'payment_method' => 'bank_transfer',
            ]
        );

        $quotaModel->newQuery()->firstOrCreate(
            ['user_id' => $user->id, 'period' => now()->format('Y-m')],
            [
                'used_count' => 0,
                'quota_limit' => 4,
            ]
        );
    }
}
