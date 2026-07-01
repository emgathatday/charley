<?php

namespace Tests\Unit;

use App\Models\AnnouncementQuota;
use App\Models\MemberSubscription;
use App\Models\MemberSubscriptionPlan;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_tier_casts_flags_and_active_scope(): void
    {
        SubscriptionTier::factory()->create([
            'name' => 'gold',
            'monthly_price' => 199,
            'ai_monthly_limit' => -1,
            'announcement_limit' => 4,
            'can_host_webinar' => 1,
            'can_initiate_message' => 0,
            'can_create_poll' => 1,
            'can_publish_events' => 0,
            'is_active' => 1,
        ]);
        SubscriptionTier::factory()->create(['name' => 'diamond', 'is_active' => false]);

        $tier = SubscriptionTier::query()->where('name', 'gold')->firstOrFail();

        $this->assertSame('199.00', $tier->monthly_price);
        $this->assertSame(-1, $tier->ai_monthly_limit);
        $this->assertSame(4, $tier->announcement_limit);
        $this->assertTrue($tier->can_host_webinar);
        $this->assertFalse($tier->can_initiate_message);
        $this->assertTrue($tier->can_create_poll);
        $this->assertFalse($tier->can_publish_events);
        $this->assertTrue($tier->is_active);
        $this->assertSame(1, SubscriptionTier::active()->count());
    }

    public function test_member_plan_casts_features_and_active_scope(): void
    {
        MemberSubscriptionPlan::factory()->create([
            'name' => 'pro',
            'features' => ['ai_unlimited', 'priority_support'],
            'is_active' => true,
        ]);
        MemberSubscriptionPlan::factory()->create(['name' => 'inactive', 'is_active' => false]);

        $plan = MemberSubscriptionPlan::query()->where('name', 'pro')->firstOrFail();

        $this->assertSame(['ai_unlimited', 'priority_support'], $plan->features);
        $this->assertTrue($plan->is_active);
        $this->assertSame(1, MemberSubscriptionPlan::active()->count());
    }

    public function test_subscription_model_relations_scopes_and_casts(): void
    {
        $user = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $tier = SubscriptionTier::factory()->create();
        $partnerSubscription = PartnerSubscription::factory()->active()->create([
            'user_id' => $user->id,
            'tier_id' => $tier->id,
            'approved_by' => $admin->id,
        ]);
        $payment = SubscriptionPayment::factory()->approved()->create([
            'partner_subscription_id' => $partnerSubscription->id,
            'amount' => 250,
            'approved_by' => $admin->id,
        ]);

        $this->assertTrue($partnerSubscription->starts_at->isValid());
        $this->assertTrue($partnerSubscription->ends_at->isValid());
        $this->assertTrue($partnerSubscription->is($payment->partnerSubscription));
        $this->assertTrue($tier->is($partnerSubscription->tier));
        $this->assertTrue($admin->is($partnerSubscription->approver));
        $this->assertSame('250.00', $payment->amount);
        $this->assertTrue($payment->period_start->isValid());
        $this->assertTrue($payment->period_end->isValid());
        $this->assertSame(1, PartnerSubscription::active()->count());
        $this->assertSame(1, SubscriptionPayment::approved()->count());
    }

    public function test_member_subscription_and_quota_casts_relations(): void
    {
        $user = User::factory()->professional()->create();
        $plan = MemberSubscriptionPlan::factory()->create();
        $memberSubscription = MemberSubscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $quota = AnnouncementQuota::factory()->create([
            'user_id' => $user->id,
            'used_count' => '2',
            'quota_limit' => '5',
        ]);

        $this->assertTrue($user->is($memberSubscription->user));
        $this->assertTrue($plan->is($memberSubscription->plan));
        $this->assertTrue($memberSubscription->starts_at->isValid());
        $this->assertTrue($memberSubscription->ends_at->isValid());
        $this->assertSame(1, MemberSubscription::active()->count());
        $this->assertTrue($user->is($quota->user));
        $this->assertSame(2, $quota->used_count);
        $this->assertSame(5, $quota->quota_limit);
    }
}
