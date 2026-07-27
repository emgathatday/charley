<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IamAdminCreatePartnerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('partner_profiles');
        Schema::dropIfExists('partner_subscriptions');
        Schema::dropIfExists('subscription_tiers');
        Schema::dropIfExists('login_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_partner_create_page_loads_active_subscription_tiers(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);

        $visibleTier = $this->createTier([
            'code' => 'growth',
            'display_name' => 'Growth Partner',
            'sort_order' => 2,
        ]);
        $hiddenTier = $this->createTier([
            'code' => 'hidden',
            'display_name' => 'Hidden Partner',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.create-partner'));

        $response->assertOk();
        $response->assertSee($visibleTier->display_name);
        $response->assertDontSee($hiddenTier->display_name);
    }

    public function test_admin_can_create_partner_with_selected_subscription_tier_and_payment(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $tier = $this->createTier([
            'code' => 'enterprise',
            'display_name' => 'Enterprise Partner',
            'duration_days' => 30,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.dashboard.iam.users.store-partner'), [
            'company_name' => 'Acme Catalysts',
            'first_name' => 'Pat',
            'last_name' => 'Partner',
            'email' => 'pat.partner@example.test',
            'subscription_tier_id' => $tier->id,
            'activate_account' => '1',
            'require_email_verification' => '0',
            'auto_renew' => '1',
            'subscription_starts_at' => '2026-07-22',
            'payment_amount' => '250.50',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'approved',
            'transaction_code' => 'INV-2026-011',
        ]);

        $response->assertRedirect();

        $user = User::where('email', 'pat.partner@example.test')->firstOrFail();
        $subscription = PartnerSubscription::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('partner', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue($user->is_verified);
        $this->assertSame($tier->id, $subscription->tier_id);
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->auto_renew);
        $this->assertSame($admin->id, $subscription->approved_by);
        $this->assertNotNull($subscription->approved_at);
        $this->assertSame('2026-07-22', $subscription->starts_at->toDateString());
        $this->assertSame('2026-08-21', $subscription->ends_at->toDateString());

        $this->assertDatabaseHas('subscription_payments', [
            'partner_subscription_id' => $subscription->id,
            'amount' => '250.50',
            'payment_method' => 'bank_transfer',
            'status' => 'approved',
            'transaction_code' => 'INV-2026-011',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_pending_partner_subscription_has_no_payment_without_payment_amount(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $tier = $this->createTier([
            'code' => 'starter',
            'display_name' => 'Starter Partner',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.dashboard.iam.users.store-partner'), [
            'company_name' => 'Pending Energy',
            'first_name' => 'Penny',
            'last_name' => 'Pending',
            'email' => 'penny.pending@example.test',
            'subscription_tier_id' => $tier->id,
            'activate_account' => '0',
            'require_email_verification' => '1',
            'auto_renew' => '0',
        ]);

        $response->assertRedirect();

        $user = User::where('email', 'penny.pending@example.test')->firstOrFail();
        $subscription = PartnerSubscription::where('user_id', $user->id)->firstOrFail();

        $this->assertFalse($user->is_verified);
        $this->assertSame('frozen', $user->status);
        $this->assertSame('pending_approval', $subscription->status);
        $this->assertFalse($subscription->auto_renew);
        $this->assertNull($subscription->approved_by);
        $this->assertNull($subscription->approved_at);
        $this->assertNull($subscription->starts_at);
        $this->assertNull($subscription->ends_at);
        $this->assertSame(0, SubscriptionPayment::where('partner_subscription_id', $subscription->id)->count());
    }

    public function test_partner_create_page_displays_selected_tier_permission_values(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $tier = $this->createTier([
            'code' => 'permissioned',
            'display_name' => 'Permissioned Partner',
        ]);
        $permissionId = DB::table('subscription_permissions')->insertGetId([
            'key' => 'announcements.create',
            'name' => 'Create announcements',
            'module' => 'announcements',
            'value_type' => 'integer',
            'default_value' => json_encode(0),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('subscription_tier_permissions')->insert([
            'tier_id' => $tier->id,
            'permission_id' => $permissionId,
            'value' => json_encode(12),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.create-partner'));

        $response->assertOk();
        $response->assertSee('Permissioned Partner');
        $response->assertSee('Create announcements: 12');
    }

    public function test_logo_upload_creates_media_file_and_stores_logo_media_id(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $tier = $this->createTier();
        $logo = UploadedFile::fake()->createWithContent('partner-logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

        $response = $this->actingAs($admin)->post(route('admin.dashboard.iam.users.store-partner'), [
            'company_name' => 'Logo Energy',
            'first_name' => 'Lena',
            'last_name' => 'Logo',
            'email' => 'lena.logo@example.test',
            'subscription_tier_id' => $tier->id,
            'activate_account' => '1',
            'require_email_verification' => '0',
            'keywords' => json_encode(['Catalyst']),
            'logo_file' => $logo,
        ]);

        $response->assertRedirect();

        $partner = User::where('email', 'lena.logo@example.test')->firstOrFail();
        $profile = PartnerProfile::where('user_id', $partner->id)->firstOrFail();
        $media = MediaFile::findOrFail($profile->logo_media_id);

        $this->assertSame('public', $media->disk);
        $this->assertSame('partner_asset', $media->upload_context);
        $this->assertSame('image', $media->file_category);
        $this->assertSame(PartnerProfile::class, $media->attachable_type);
        $this->assertSame($profile->id, $media->attachable_id);
        $this->assertFalse($media->is_orphan);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_keywords_submit_as_required_json_and_preserve_old_input(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $tier = $this->createTier();

        $response = $this->actingAs($admin)->post(route('admin.dashboard.iam.users.store-partner'), [
            'company_name' => 'Keyword Energy',
            'first_name' => 'Kelly',
            'last_name' => 'Keyword',
            'email' => 'kelly.keyword@example.test',
            'subscription_tier_id' => $tier->id,
            'activate_account' => '1',
            'require_email_verification' => '0',
            'keywords' => json_encode(['Catalyst', 'Reformer', 'Catalyst']),
        ]);

        $response->assertRedirect();

        $partner = User::where('email', 'kelly.keyword@example.test')->firstOrFail();
        $profile = PartnerProfile::where('user_id', $partner->id)->firstOrFail();
        $this->assertSame(['Catalyst', 'Reformer'], $profile->keywords);

        $invalid = $this->actingAs($admin)->from(route('admin.dashboard.iam.users.create-partner'))->post(route('admin.dashboard.iam.users.store-partner'), [
            'company_name' => 'Missing Keywords',
            'first_name' => 'Mira',
            'last_name' => 'Missing',
            'email' => 'mira.missing@example.test',
            'subscription_tier_id' => $tier->id,
            'keywords' => json_encode([]),
        ]);

        $invalid->assertRedirect(route('admin.dashboard.iam.users.create-partner'));
        $invalid->assertSessionHasErrors('keywords');
        $invalid->assertSessionHasInput('company_name', 'Missing Keywords');
        $invalid->assertSessionHasInput('keywords', json_encode([]));

        $oldInputPage = $this->actingAs($admin)
            ->withSession(['_old_input' => ['keywords' => json_encode(['Catalyst', 'Reformer'])]])
            ->get(route('admin.dashboard.iam.users.create-partner'));

        $oldInputPage->assertOk();
        $oldInputPage->assertSee('data-keyword-chip', false);
        $oldInputPage->assertSee('Catalyst');
        $oldInputPage->assertSee('Reformer');
        $oldInputPage->assertSee('handleKeywordInput', false);
        $oldInputPage->assertSee('removeKeywordTag', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createUser(string $email, array $overrides = []): User
    {
        return User::create(array_merge([
            'username' => str_replace(['@example.test', '.'], ['', '-'], $email),
            'first_name' => 'Admin',
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
     * @param  array<string, mixed>  $overrides
     */
    private function createTier(array $overrides = []): SubscriptionTier
    {
        $attributes = array_merge([
            'name' => $overrides['display_name'] ?? 'Dynamic Partner',
            'monthly_price' => 99.00,
            'ai_monthly_limit' => 100,
            'announcement_frequency' => 'monthly',
            'announcement_limit' => 5,
            'can_host_webinar' => false,
            'can_initiate_message' => false,
            'can_create_poll' => false,
            'can_publish_events' => false,
            'is_active' => true,
            'code' => 'dynamic',
            'display_name' => 'Dynamic Partner',
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

        Schema::create('subscription_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('module')->nullable();
            $table->string('value_type')->default('boolean');
            $table->json('default_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscription_tier_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tier_id')->constrained('subscription_tiers')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('subscription_permissions')->cascadeOnDelete();
            $table->json('value');
            $table->timestamps();
        });

        Schema::create('partner_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tier_id')->constrained('subscription_tiers')->cascadeOnDelete();
            $table->string('status')->default('pending_approval');
            $table->boolean('auto_renew')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('media_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('uploader_id')->nullable();
            $table->string('disk')->default('public');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('attachable_type')->nullable()->index();
            $table->unsignedBigInteger('attachable_id')->nullable()->index();
            $table->string('upload_context')->nullable();
            $table->string('file_category')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_watermarked')->default(false);
            $table->string('watermarked_file_path')->nullable();
            $table->string('streaming_url')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->string('processing_status')->nullable();
            $table->text('processing_error')->nullable();
            $table->boolean('is_orphan')->default(false);
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

        Schema::create('subscription_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_subscription_id')->constrained('partner_subscriptions')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('bank_transfer');
            $table->foreignId('payment_proof_media_id')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('status')->default('pending');
            $table->string('transaction_code')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
}
