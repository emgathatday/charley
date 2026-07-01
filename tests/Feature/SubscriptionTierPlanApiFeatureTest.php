<?php

namespace Tests\Feature;

use App\Models\MemberSubscriptionPlan;
use App\Models\SubscriptionTier;
use App\Models\User;
use Database\Seeders\SubscriptionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionTierPlanApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_schema_contains_expected_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('subscription_tiers', [
            'id',
            'name',
            'monthly_price',
            'ai_monthly_limit',
            'announcement_frequency',
            'announcement_limit',
            'can_host_webinar',
            'can_initiate_message',
            'can_create_poll',
            'can_publish_events',
            'is_active',
        ]));
        $this->assertTrue(Schema::hasColumns('member_subscription_plans', [
            'id',
            'name',
            'display_name',
            'monthly_price',
            'ai_monthly_limit',
            'features',
            'is_active',
            'created_at',
            'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('partner_subscriptions', [
            'id',
            'user_id',
            'tier_id',
            'status',
            'approved_by',
            'approved_at',
            'starts_at',
            'ends_at',
        ]));
        $this->assertTrue(Schema::hasColumns('subscription_payments', [
            'id',
            'partner_subscription_id',
            'amount',
            'payment_method',
            'payment_proof_media_id',
            'period_start',
            'period_end',
            'status',
            'transaction_code',
            'approved_by',
        ]));
        $this->assertTrue(Schema::hasColumns('member_subscriptions', [
            'id',
            'user_id',
            'plan_id',
            'status',
            'starts_at',
            'ends_at',
            'payment_method',
        ]));
        $this->assertTrue(Schema::hasColumns('announcement_quotas', [
            'id',
            'user_id',
            'period',
            'used_count',
            'quota_limit',
        ]));
    }

    public function test_subscription_seeder_is_idempotent(): void
    {
        User::factory()->professional()->create();
        $seeder = new SubscriptionSeeder();

        $seeder->run();
        $seeder->run();

        $this->assertSame(3, SubscriptionTier::query()->count());
        $this->assertSame(1, MemberSubscriptionPlan::query()->count());
        $this->assertDatabaseHas('subscription_tiers', [
            'name' => 'platinum',
            'ai_monthly_limit' => -1,
            'can_publish_events' => true,
        ]);
        $this->assertDatabaseHas('member_subscription_plans', [
            'name' => 'professional-ai-unlimited',
            'ai_monthly_limit' => -1,
        ]);
    }

    public function test_tier_and_plan_api_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/subscription-tiers')->assertUnauthorized();
        $this->getJson('/api/v1/member-subscription-plans')->assertUnauthorized();
    }

    public function test_tier_api_returns_sorted_resource_collection_and_filters_active(): void
    {
        $user = User::factory()->professional()->create();
        $inactive = SubscriptionTier::factory()->create(['name' => 'platinum', 'monthly_price' => 999, 'is_active' => false]);
        $second = SubscriptionTier::factory()->create(['name' => 'diamond', 'monthly_price' => 499, 'is_active' => true]);
        $first = SubscriptionTier::factory()->create(['name' => 'gold', 'monthly_price' => 199, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/api/v1/subscription-tiers?active=1')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'monthly_price',
                        'ai_monthly_limit',
                        'announcement_frequency',
                        'announcement_limit',
                        'can_host_webinar',
                        'can_initiate_message',
                        'can_create_poll',
                        'can_publish_events',
                        'is_active',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id);

        $this->actingAs($user)
            ->getJson("/api/v1/subscription-tiers/{$inactive->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $inactive->id);
    }

    public function test_plan_api_returns_sorted_resource_collection_and_filters_active(): void
    {
        $user = User::factory()->professional()->create();
        MemberSubscriptionPlan::factory()->create(['name' => 'inactive', 'display_name' => 'Inactive', 'monthly_price' => 99, 'is_active' => false]);
        $second = MemberSubscriptionPlan::factory()->create(['name' => 'pro', 'display_name' => 'Pro', 'monthly_price' => 49, 'is_active' => true]);
        $first = MemberSubscriptionPlan::factory()->create(['name' => 'basic', 'display_name' => 'Basic', 'monthly_price' => 19, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/api/v1/member-subscription-plans?active=1')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'display_name',
                        'monthly_price',
                        'ai_monthly_limit',
                        'features',
                        'is_active',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id);
    }

    public function test_admin_can_create_update_and_delete_tier_and_plan(): void
    {
        $admin = User::factory()->admin()->create();

        $tierId = $this->actingAs($admin)
            ->postJson('/api/v1/subscription-tiers', [
                'name' => 'gold',
                'monthly_price' => 199,
                'ai_monthly_limit' => 500,
                'announcement_frequency' => 'monthly',
                'announcement_limit' => 4,
                'can_host_webinar' => false,
                'can_initiate_message' => true,
                'can_create_poll' => false,
                'can_publish_events' => false,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'gold')
            ->json('data.id');

        $this->actingAs($admin)
            ->putJson("/api/v1/subscription-tiers/{$tierId}", [
                'name' => 'gold',
                'monthly_price' => 249,
                'ai_monthly_limit' => -1,
                'announcement_frequency' => 'weekly',
                'announcement_limit' => 8,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.monthly_price', '249.00')
            ->assertJsonPath('data.is_active', false);

        $planId = $this->actingAs($admin)
            ->postJson('/api/v1/member-subscription-plans', [
                'name' => 'professional',
                'display_name' => 'Professional',
                'monthly_price' => 49,
                'ai_monthly_limit' => -1,
                'features' => ['ai_unlimited', 'priority_support'],
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.features.0', 'ai_unlimited')
            ->json('data.id');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/subscription-tiers/{$tierId}")
            ->assertOk()
            ->assertJsonPath('data.id', $tierId);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/member-subscription-plans/{$planId}")
            ->assertOk()
            ->assertJsonPath('data.id', $planId);
    }

    public function test_non_admin_cannot_write_tiers_or_plans(): void
    {
        $user = User::factory()->professional()->create();
        $tier = SubscriptionTier::factory()->create(['name' => 'gold']);
        $plan = MemberSubscriptionPlan::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/subscription-tiers', [
                'name' => 'diamond',
                'monthly_price' => 499,
                'ai_monthly_limit' => 2000,
                'announcement_frequency' => 'weekly',
                'announcement_limit' => 12,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->putJson("/api/v1/subscription-tiers/{$tier->id}", [
                'name' => 'gold',
                'monthly_price' => 299,
                'ai_monthly_limit' => 500,
                'announcement_frequency' => 'monthly',
                'announcement_limit' => 4,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson('/api/v1/member-subscription-plans', [
                'name' => 'denied',
                'display_name' => 'Denied',
                'monthly_price' => 19,
                'ai_monthly_limit' => 100,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson("/api/v1/member-subscription-plans/{$plan->id}")
            ->assertForbidden();
    }

    public function test_tier_and_plan_validation_and_missing_resources(): void
    {
        $admin = User::factory()->admin()->create();
        $tier = SubscriptionTier::factory()->create(['name' => 'gold']);
        $plan = MemberSubscriptionPlan::factory()->create(['name' => 'professional']);

        $this->actingAs($admin)
            ->postJson('/api/v1/subscription-tiers', [
                'name' => $tier->name,
                'monthly_price' => -1,
                'ai_monthly_limit' => -2,
                'announcement_frequency' => 'daily',
                'announcement_limit' => -1,
                'is_active' => 'bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'monthly_price',
                'ai_monthly_limit',
                'announcement_frequency',
                'announcement_limit',
                'is_active',
            ]);

        $this->actingAs($admin)
            ->postJson('/api/v1/member-subscription-plans', [
                'name' => $plan->name,
                'display_name' => null,
                'monthly_price' => -1,
                'ai_monthly_limit' => -2,
                'features' => 'not-array',
                'is_active' => 'bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'display_name',
                'monthly_price',
                'ai_monthly_limit',
                'features',
                'is_active',
            ]);

        $this->actingAs($admin)->getJson('/api/v1/subscription-tiers/999999')->assertNotFound();
        $this->actingAs($admin)->getJson('/api/v1/member-subscription-plans/999999')->assertNotFound();
    }
}
