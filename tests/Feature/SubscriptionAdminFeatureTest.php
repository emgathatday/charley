<?php

namespace Tests\Feature;

use App\Models\MemberSubscriptionPlan;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPermission;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_admin_routes_require_admin_access(): void
    {
        $this->get('/dashboard/subscriptions')->assertRedirect('/login');

        $user = User::factory()->professional()->create();

        $this->actingAs($user)
            ->get('/dashboard/subscriptions')
            ->assertForbidden();
    }

    public function test_admin_index_renders_english_tier_dashboard_without_secondary_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $tier = $this->createTier(['code' => 'founding', 'name' => 'founding', 'display_name' => 'Founding Partner', 'monthly_price' => 199]);
        $this->createTier(['code' => 'private', 'name' => 'private', 'display_name' => 'Private Partner', 'is_public' => false, 'is_active' => false]);
        PartnerSubscription::factory()->create([
            'tier_id' => $tier->id,
            'status' => 'pending_approval',
        ]);

        $this->actingAs($admin)
            ->get('/dashboard/subscriptions?keyword=founding')
            ->assertOk()
            ->assertSee('Subscription Tiers')
            ->assertSee('Partner subscription tiers')
            ->assertSee('Create tier')
            ->assertSee('Founding Partner')
            ->assertDontSee('Member plans')
            ->assertDontSee('Subscription payments')
            ->assertDontSee('Announcement quotas')
            ->assertDontSee('Private Partner', false);
    }

    public function test_admin_can_create_update_tiers_and_member_plans_with_dynamic_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $quotaPermission = SubscriptionPermission::query()->create([
            'key' => 'announcement_limit',
            'name' => 'Announcement limit',
            'module' => 'announcements',
            'value_type' => 'integer',
            'default_value' => 2,
            'is_active' => true,
        ]);
        $booleanPermission = SubscriptionPermission::query()->create([
            'key' => 'can_host_webinar',
            'name' => 'Can host webinar',
            'module' => 'events',
            'value_type' => 'boolean',
            'default_value' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard/subscriptions/tiers/create')
            ->assertOk()
            ->assertSee('Create Subscription Tier')
            ->assertSee('Tier details')
            ->assertSee('Permissions');

        $this->actingAs($admin)
            ->post('/dashboard/subscriptions/tiers', [
                'code' => 'founding_partner',
                'name' => 'founding_partner',
                'display_name' => 'Founding Partner',
                'description' => 'Launch partner plan',
                'monthly_price' => '199.00',
                'billing_cycle' => 'monthly',
                'duration_days' => '',
                'sort_order' => '1',
                'is_public' => '1',
                'is_active' => '1',
                'permissions' => [
                    $quotaPermission->id => ['enabled' => '1', 'value' => '8'],
                    $booleanPermission->id => ['enabled' => '1', 'value' => '1'],
                ],
            ])
            ->assertRedirect('/dashboard/subscriptions');

        $tier = SubscriptionTier::query()->where('code', 'founding_partner')->firstOrFail();
        $this->assertSame(8, $tier->tierPermissions()->where('permission_id', $quotaPermission->id)->firstOrFail()->value);
        $this->assertTrue($tier->tierPermissions()->where('permission_id', $booleanPermission->id)->exists());

        $this->actingAs($admin)
            ->put("/dashboard/subscriptions/tiers/{$tier->id}", [
                'code' => 'founding_partner',
                'name' => 'founding_partner',
                'display_name' => 'Founding Partner Plus',
                'description' => 'Launch partner plan',
                'monthly_price' => '249.00',
                'billing_cycle' => 'yearly',
                'duration_days' => '365',
                'sort_order' => '2',
                'is_public' => '0',
                'is_active' => '0',
                'permissions' => [
                    $quotaPermission->id => ['enabled' => '1', 'value' => '12'],
                    $booleanPermission->id => ['enabled' => '0', 'value' => '1'],
                ],
            ])
            ->assertRedirect('/dashboard/subscriptions');

        $this->assertDatabaseHas('subscription_tiers', [
            'id' => $tier->id,
            'display_name' => 'Founding Partner Plus',
            'monthly_price' => '249.00',
            'is_public' => false,
            'is_active' => false,
        ]);
        $tier->refresh();
        $this->assertSame(12, $tier->tierPermissions()->where('permission_id', $quotaPermission->id)->firstOrFail()->value);
        $this->assertFalse($tier->tierPermissions()->where('permission_id', $booleanPermission->id)->exists());

        $this->actingAs($admin)
            ->post('/dashboard/subscriptions/member-plans', [
                'name' => 'professional',
                'display_name' => 'Professional',
                'monthly_price' => '49.00',
                'ai_monthly_limit' => '-1',
                'features' => 'ai_unlimited, priority_support',
                'is_active' => '1',
            ])
            ->assertRedirect('/dashboard/subscriptions');

        $plan = MemberSubscriptionPlan::query()->where('name', 'professional')->firstOrFail();

        $this->assertSame(['ai_unlimited', 'priority_support'], $plan->features);
    }

    public function test_admin_can_approve_cancel_and_review_payments(): void
    {
        $admin = User::factory()->admin()->create();
        $subscription = PartnerSubscription::factory()->create(['tier_id' => $this->createTier()->id, 'status' => 'pending_approval']);
        $payment = SubscriptionPayment::factory()->create([
            'partner_subscription_id' => $subscription->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/dashboard/subscriptions/partner-subscriptions/{$subscription->id}/approve")
            ->assertRedirect('/dashboard/subscriptions');

        $this->assertDatabaseHas('partner_subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
            'approved_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post("/dashboard/subscriptions/partner-subscriptions/{$subscription->id}/cancel")
            ->assertRedirect('/dashboard/subscriptions');

        $this->assertDatabaseHas('partner_subscriptions', [
            'id' => $subscription->id,
            'status' => 'cancelled',
        ]);

        $this->actingAs($admin)
            ->post("/dashboard/subscriptions/payments/{$payment->id}/approve")
            ->assertRedirect('/dashboard/subscriptions');

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post("/dashboard/subscriptions/payments/{$payment->id}/reject")
            ->assertRedirect('/dashboard/subscriptions');

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'rejected',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_validation_rejects_invalid_subscription_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        $this->createTier(['code' => 'founding_partner', 'name' => 'founding_partner']);
        MemberSubscriptionPlan::factory()->create(['name' => 'professional']);

        $this->actingAs($admin)
            ->from('/dashboard/subscriptions/tiers/create')
            ->post('/dashboard/subscriptions/tiers', [
                'code' => 'founding_partner',
                'name' => 'founding_partner',
                'display_name' => '',
                'monthly_price' => '-1',
                'billing_cycle' => 'daily',
                'duration_days' => '0',
                'sort_order' => '-1',
                'is_public' => 'bad',
                'is_active' => 'bad',
            ])
            ->assertRedirect('/dashboard/subscriptions/tiers/create')
            ->assertSessionHasErrors([
                'code',
                'name',
                'display_name',
                'monthly_price',
                'billing_cycle',
                'duration_days',
                'sort_order',
                'is_public',
                'is_active',
            ]);

        $this->actingAs($admin)
            ->from('/dashboard/subscriptions/member-plans/create')
            ->post('/dashboard/subscriptions/member-plans', [
                'name' => 'professional',
                'display_name' => '',
                'monthly_price' => '-1',
                'ai_monthly_limit' => '-2',
                'is_active' => 'bad',
            ])
            ->assertRedirect('/dashboard/subscriptions/member-plans/create')
            ->assertSessionHasErrors([
                'name',
                'display_name',
                'monthly_price',
                'ai_monthly_limit',
                'is_active',
            ]);
    }

    public function test_subscription_blade_views_do_not_fetch_subscription_data(): void
    {
        foreach ([
            resource_path('views/admin/subscriptions/index.blade.php'),
            resource_path('views/admin/subscriptions/tiers/create.blade.php'),
            resource_path('views/admin/subscriptions/tiers/edit.blade.php'),
            resource_path('views/admin/subscriptions/tiers/_form.blade.php'),
        ] as $view) {
            $contents = file_get_contents($view);

            $this->assertStringNotContainsString('::query(', $contents, $view);
            $this->assertStringNotContainsString('::where(', $contents, $view);
            $this->assertStringNotContainsString('::find', $contents, $view);
            $this->assertStringNotContainsString('DB::', $contents, $view);
        }
    }

    private function createTier(array $attributes = []): SubscriptionTier
    {
        $tier = new SubscriptionTier();

        $tier->forceFill(array_merge([
            'code' => 'partner_'.fake()->unique()->numerify('####'),
            'name' => 'partner_'.fake()->unique()->numerify('####'),
            'display_name' => 'Partner Plan',
            'description' => null,
            'monthly_price' => 99,
            'billing_cycle' => 'monthly',
            'duration_days' => null,
            'sort_order' => 1,
            'is_public' => true,
            'is_active' => true,
            'ai_monthly_limit' => 0,
            'announcement_frequency' => 'monthly',
            'announcement_limit' => 0,
            'can_host_webinar' => false,
            'can_initiate_message' => false,
            'can_create_poll' => false,
            'can_publish_events' => false,
        ], $attributes))->save();

        return $tier;
    }
}
