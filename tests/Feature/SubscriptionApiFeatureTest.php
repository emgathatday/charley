<?php

namespace Tests\Feature;

use App\Models\AnnouncementQuota;
use App\Models\MediaFile;
use App\Models\MemberSubscription;
use App\Models\MemberSubscriptionPlan;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/partner-subscriptions')->assertUnauthorized();
        $this->getJson('/api/v1/subscription-payments')->assertUnauthorized();
        $this->getJson('/api/v1/member-subscriptions')->assertUnauthorized();
        $this->getJson('/api/v1/announcement-quotas')->assertUnauthorized();
    }

    public function test_partner_subscription_resource_flow_and_authorization(): void
    {
        $user = User::factory()->professional()->create();
        $otherUser = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $tier = SubscriptionTier::factory()->create();

        $createResponse = $this->actingAs($user)
            ->postJson('/api/v1/partner-subscriptions', [
                'tier_id' => $tier->id,
                'starts_at' => now()->toDateTimeString(),
                'ends_at' => now()->addMonth()->toDateTimeString(),
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'tier_id',
                    'status',
                    'approved_by',
                    'approved_at',
                    'starts_at',
                    'ends_at',
                    'tier',
                    'payments',
                ],
            ])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'pending_approval');

        $subscriptionId = $createResponse->json('data.id');

        $this->actingAs($user)
            ->postJson("/api/v1/partner-subscriptions/{$subscriptionId}/approve")
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson("/api/v1/partner-subscriptions/{$subscriptionId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.approved_by', $admin->id);

        $this->actingAs($otherUser)
            ->postJson("/api/v1/partner-subscriptions/{$subscriptionId}/cancel")
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson("/api/v1/partner-subscriptions/{$subscriptionId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_subscription_validation_rejects_invalid_and_missing_foreign_keys(): void
    {
        $user = User::factory()->professional()->create();
        $subscription = PartnerSubscription::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/partner-subscriptions', [
                'tier_id' => 999999,
                'starts_at' => now()->toDateTimeString(),
                'ends_at' => now()->subDay()->toDateTimeString(),
                'status' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tier_id', 'ends_at', 'status']);

        $this->actingAs($user)
            ->postJson('/api/v1/subscription-payments', [
                'partner_subscription_id' => 999999,
                'amount' => -1,
                'payment_proof_media_id' => 999999,
                'period_start' => now()->toDateString(),
                'period_end' => now()->subDay()->toDateString(),
                'status' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'partner_subscription_id',
                'amount',
                'payment_proof_media_id',
                'period_end',
                'status',
            ]);

        $this->actingAs($user)
            ->postJson('/api/v1/subscription-payments', [
                'partner_subscription_id' => $subscription->id,
                'amount' => 100,
                'payment_proof_media_id' => null,
                'period_start' => null,
                'period_end' => null,
            ])
            ->assertCreated()
            ->assertJsonPath('data.payment_proof_media_id', null)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_subscription_payment_uses_media_id_and_manager_approval(): void
    {
        $user = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $subscription = PartnerSubscription::factory()->create(['status' => 'active']);
        $media = $this->createMediaFile($user);

        $createResponse = $this->actingAs($user)
            ->postJson('/api/v1/subscription-payments', [
                'partner_subscription_id' => $subscription->id,
                'amount' => 199.99,
                'payment_proof_media_id' => $media->id,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'transaction_code' => 'PAY-001',
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
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
                    'payment_proof_media',
                ],
            ])
            ->assertJsonPath('data.payment_proof_media_id', $media->id)
            ->assertJsonPath('data.transaction_code', 'PAY-001');

        $paymentId = $createResponse->json('data.id');

        $this->actingAs($user)
            ->postJson("/api/v1/subscription-payments/{$paymentId}/approve")
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson("/api/v1/subscription-payments/{$paymentId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by', $admin->id);
    }

    public function test_member_subscription_resource_flow_and_cancel_authorization(): void
    {
        $user = User::factory()->professional()->create();
        $otherUser = User::factory()->professional()->create();
        $plan = MemberSubscriptionPlan::factory()->create();

        $createResponse = $this->actingAs($user)
            ->postJson('/api/v1/member-subscriptions', [
                'plan_id' => $plan->id,
                'starts_at' => now()->toDateTimeString(),
                'ends_at' => now()->addMonth()->toDateTimeString(),
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'plan_id',
                    'status',
                    'starts_at',
                    'ends_at',
                    'payment_method',
                    'plan',
                ],
            ])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'active');

        $subscriptionId = $createResponse->json('data.id');

        $this->actingAs($otherUser)
            ->postJson("/api/v1/member-subscriptions/{$subscriptionId}/cancel")
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson("/api/v1/member-subscriptions/{$subscriptionId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_announcement_quota_resource_flow_and_exhaustion(): void
    {
        $user = User::factory()->professional()->create();
        $otherUser = User::factory()->professional()->create();

        $createResponse = $this->actingAs($user)
            ->postJson('/api/v1/announcement-quotas', [
                'period' => '2026-06',
                'used_count' => 0,
                'quota_limit' => 1,
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'period',
                    'used_count',
                    'quota_limit',
                ],
            ])
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.period', '2026-06');

        $quotaId = $createResponse->json('data.id');

        $this->actingAs($otherUser)
            ->postJson("/api/v1/announcement-quotas/{$quotaId}/consume")
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson("/api/v1/announcement-quotas/{$quotaId}/consume")
            ->assertOk()
            ->assertJsonPath('data.used_count', 1);

        $this->actingAs($user)
            ->postJson("/api/v1/announcement-quotas/{$quotaId}/consume")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quota']);
    }

    public function test_missing_resources_return_not_found(): void
    {
        $user = User::factory()->professional()->create();

        $this->actingAs($user)->getJson('/api/v1/partner-subscriptions/999999')->assertNotFound();
        $this->actingAs($user)->getJson('/api/v1/subscription-payments/999999')->assertNotFound();
        $this->actingAs($user)->getJson('/api/v1/member-subscriptions/999999')->assertNotFound();
        $this->actingAs($user)->getJson('/api/v1/announcement-quotas/999999')->assertNotFound();
    }

    public function test_subscription_related_records_cascade_or_null_on_delete(): void
    {
        $user = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $subscription = PartnerSubscription::factory()->create([
            'user_id' => $user->id,
            'approved_by' => $admin->id,
        ]);
        $payment = SubscriptionPayment::factory()->create([
            'partner_subscription_id' => $subscription->id,
            'approved_by' => $admin->id,
        ]);
        $memberSubscription = MemberSubscription::factory()->create(['user_id' => $user->id]);
        $quota = AnnouncementQuota::factory()->create(['user_id' => $user->id]);

        $admin->delete();

        $this->assertDatabaseHas('partner_subscriptions', ['id' => $subscription->id, 'approved_by' => null]);
        $this->assertDatabaseHas('subscription_payments', ['id' => $payment->id, 'approved_by' => null]);

        $subscription->delete();

        $this->assertDatabaseMissing('subscription_payments', ['id' => $payment->id]);

        $user->delete();

        $this->assertDatabaseMissing('member_subscriptions', ['id' => $memberSubscription->id]);
        $this->assertDatabaseMissing('announcement_quotas', ['id' => $quota->id]);
    }

    private function createMediaFile(User $user): MediaFile
    {
        return MediaFile::query()->create([
            'uploader_id' => $user->id,
            'disk' => 's3',
            'path' => 'proofs/payment-proof.jpg',
            'original_name' => 'payment-proof.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'upload_context' => 'general',
            'file_category' => 'image',
            'is_orphan' => false,
        ]);
    }
}

