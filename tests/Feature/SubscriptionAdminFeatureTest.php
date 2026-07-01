<?php

namespace Tests\Feature;

use App\Models\AnnouncementQuota;
use App\Models\MediaFile;
use App\Models\MemberSubscriptionPlan;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
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

    public function test_admin_index_renders_stats_filters_payment_proofs_and_sidebar(): void
    {
        $admin = User::factory()->admin()->create();
        $tier = SubscriptionTier::factory()->create(['name' => 'gold', 'monthly_price' => 199]);
        $plan = MemberSubscriptionPlan::factory()->create(['name' => 'professional', 'display_name' => 'Professional']);
        $subscription = PartnerSubscription::factory()->create([
            'tier_id' => $tier->id,
            'status' => 'pending_approval',
        ]);
        $media = $this->createMediaFile($subscription->user);
        SubscriptionPayment::factory()->create([
            'partner_subscription_id' => $subscription->id,
            'payment_proof_media_id' => $media->id,
            'status' => 'pending',
        ]);
        AnnouncementQuota::factory()->create([
            'user_id' => $subscription->user_id,
            'period' => '2026-06',
            'used_count' => 1,
            'quota_limit' => 4,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard/subscriptions?payment_status=pending&quota_period=2026-06')
            ->assertOk()
            ->assertSee('Subscriptions')
            ->assertSee('Partner tiers')
            ->assertSee('Member plans')
            ->assertSee('Pending approvals')
            ->assertSee('Subscription payments')
            ->assertSee('Announcement quotas')
            ->assertSee('payment-proof.jpg')
            ->assertSee('Professional')
            ->assertSee('Quotas')
            ->assertSee('Payments');
    }

    public function test_admin_can_create_update_tiers_and_member_plans(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/dashboard/subscriptions/tiers/create')
            ->assertOk()
            ->assertSee('Create Subscription Tier');

        $this->actingAs($admin)
            ->post('/dashboard/subscriptions/tiers', [
                'name' => 'gold',
                'monthly_price' => '199.00',
                'ai_monthly_limit' => '500',
                'announcement_frequency' => 'monthly',
                'announcement_limit' => '4',
                'can_host_webinar' => '0',
                'can_initiate_message' => '1',
                'can_create_poll' => '0',
                'can_publish_events' => '0',
                'is_active' => '1',
            ])
            ->assertRedirect('/dashboard/subscriptions');

        $tier = SubscriptionTier::query()->where('name', 'gold')->firstOrFail();

        $this->actingAs($admin)
            ->put("/dashboard/subscriptions/tiers/{$tier->id}", [
                'name' => 'gold',
                'monthly_price' => '249.00',
                'ai_monthly_limit' => '-1',
                'announcement_frequency' => 'weekly',
                'announcement_limit' => '8',
                'can_host_webinar' => '1',
                'can_initiate_message' => '1',
                'can_create_poll' => '1',
                'can_publish_events' => '1',
                'is_active' => '0',
            ])
            ->assertRedirect('/dashboard/subscriptions');

        $this->assertDatabaseHas('subscription_tiers', [
            'id' => $tier->id,
            'monthly_price' => '249.00',
            'is_active' => false,
        ]);

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
        $subscription = PartnerSubscription::factory()->create(['status' => 'pending_approval']);
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
        SubscriptionTier::factory()->create(['name' => 'gold']);
        MemberSubscriptionPlan::factory()->create(['name' => 'professional']);

        $this->actingAs($admin)
            ->from('/dashboard/subscriptions/tiers/create')
            ->post('/dashboard/subscriptions/tiers', [
                'name' => 'gold',
                'monthly_price' => '-1',
                'ai_monthly_limit' => '-2',
                'announcement_frequency' => 'daily',
                'announcement_limit' => '-1',
                'can_host_webinar' => 'bad',
                'can_initiate_message' => 'bad',
                'can_create_poll' => 'bad',
                'can_publish_events' => 'bad',
                'is_active' => 'bad',
            ])
            ->assertRedirect('/dashboard/subscriptions/tiers/create')
            ->assertSessionHasErrors([
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

    private function createMediaFile(User $user): MediaFile
    {
        return MediaFile::query()->create([
            'uploader_id' => $user->id,
            'disk' => 's3',
            'path' => 'proofs/admin-payment-proof.jpg',
            'original_name' => 'payment-proof.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'upload_context' => 'general',
            'file_category' => 'image',
            'is_orphan' => false,
        ]);
    }
}
