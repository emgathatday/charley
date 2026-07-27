<?php

namespace Tests\Feature;

use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerProfileSubscriptionDisplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('verification_requests');
        Schema::dropIfExists('partner_profiles');
        Schema::dropIfExists('partner_subscriptions');
        Schema::dropIfExists('subscription_tiers');
        Schema::dropIfExists('login_tokens');
        Schema::dropIfExists('partner_plant_types');
        Schema::dropIfExists('plant_types');
        Schema::dropIfExists('unverified_member_profiles');
        Schema::dropIfExists('engineer_profiles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_partner_create_stores_active_subscription_pointer_on_profile(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $tier = $this->createTier(['display_name' => 'Enterprise Access', 'code' => 'enterprise_access']);

        $response = $this->actingAs($admin)->post(route('admin.dashboard.iam.users.store-partner'), [
            'company_name' => 'Pointer Energy',
            'first_name' => 'Paula',
            'last_name' => 'Pointer',
            'email' => 'paula.pointer@example.test',
            'subscription_tier_id' => $tier->id,
            'activate_account' => '1',
            'require_email_verification' => '0',
            'subscription_starts_at' => '2026-07-22',
        ]);

        $response->assertRedirect();

        $partner = User::where('email', 'paula.pointer@example.test')->firstOrFail();
        $subscription = PartnerSubscription::where('user_id', $partner->id)->firstOrFail();
        $profile = PartnerProfile::where('user_id', $partner->id)->firstOrFail();

        $this->assertSame($subscription->id, $profile->active_partner_subscription_id);
        $this->assertSame('active', $profile->subscription_status);
        $this->assertNull($profile->partner_tier);
    }

    public function test_partner_listing_displays_tier_from_active_subscription_not_legacy_profile_tier(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $partner = $this->createUser('partner@example.test', ['role' => 'partner']);
        $tier = $this->createTier(['display_name' => 'Enterprise Access', 'code' => 'enterprise_access']);
        $subscription = PartnerSubscription::create([
            'user_id' => $partner->id,
            'tier_id' => $tier->id,
            'status' => 'active',
            'auto_renew' => false,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        PartnerProfile::create([
            'user_id' => $partner->id,
            'company_name' => 'Dynamic Tier Company',
            'partner_tier' => 'Legacy Gold',
            'active_partner_subscription_id' => $subscription->id,
            'layout_template' => 'layout_1',
            'feed_highlight_enabled' => true,
            'subscription_status' => 'active',
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.partners'));

        $response->assertOk();
        $response->assertSee('Dynamic Tier Company');
        $response->assertSee('Enterprise Access');
        $response->assertDontSee('Legacy Gold');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createUser(string $email, array $overrides = []): User
    {
        return User::create(array_merge([
            'username' => str_replace(['@example.test', '.'], ['', '-'], $email),
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_verified' => true,
            'verified_at' => now(),
            'verification_expires_at' => null,
            'status' => 'active',
            'login_attempts' => 0,
            'mfa_enabled' => false,
        ], $overrides));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTier(array $overrides = []): SubscriptionTier
    {
        $attributes = array_merge([
            'name' => $overrides['display_name'] ?? 'Dynamic Access',
            'monthly_price' => 99.00,
            'ai_monthly_limit' => 100,
            'announcement_frequency' => 'monthly',
            'announcement_limit' => 5,
            'can_host_webinar' => false,
            'can_initiate_message' => false,
            'can_create_poll' => false,
            'can_publish_events' => false,
            'is_active' => true,
            'code' => 'dynamic_access',
            'display_name' => 'Dynamic Access',
            'description' => 'Dynamic subscription tier.',
            'billing_cycle' => 'monthly',
            'duration_days' => null,
            'sort_order' => 10,
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        $id = DB::table('subscription_tiers')->insertGetId($attributes);

        return SubscriptionTier::findOrFail($id);
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->nullable()->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('unverified_member');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->text('mfa_secret')->nullable();
            $table->json('mfa_recovery_codes')->nullable();
            $table->timestamp('self_frozen_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('engineer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->json('industry_specialization')->nullable();
            $table->json('expertise_tags')->nullable();
            $table->integer('experience_years')->nullable();
        });

        Schema::create('unverified_member_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('field_of_study')->nullable();
            $table->json('expertise_tags')->nullable();
            $table->integer('experience_years')->nullable();
        });

        Schema::create('plant_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('login_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token');
            $table->string('type');
            $table->boolean('is_used')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_tiers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->integer('ai_monthly_limit')->default(0);
            $table->string('announcement_frequency')->default('monthly');
            $table->integer('announcement_limit')->default(0);
            $table->boolean('can_host_webinar')->default(false);
            $table->boolean('can_initiate_message')->default(false);
            $table->boolean('can_create_poll')->default(false);
            $table->boolean('can_publish_events')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('code')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->integer('duration_days')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('partner_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tier_id')->constrained('subscription_tiers')->cascadeOnDelete();
            $table->string('status')->default('pending_approval');
            $table->boolean('auto_renew')->default(false);
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('company_name');
            $table->foreignId('logo_media_id')->nullable();
            $table->text('overview')->nullable();
            $table->foreignId('active_partner_subscription_id')->nullable();
            $table->string('partner_tier')->nullable();
            $table->foreignId('plant_type_id')->nullable();
            $table->json('keywords')->nullable();
            $table->json('references')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('website')->nullable();
            $table->smallInteger('founded_year')->nullable();
            $table->json('social_links')->nullable();
            $table->string('layout_template')->default('layout_1');
            $table->boolean('feed_highlight_enabled')->default(true);
            $table->string('subscription_status')->default('inactive');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->string('approval_status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('verification_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }
}
