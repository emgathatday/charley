<?php

namespace Tests\Feature;

use App\Models\EngineerProfile;
use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PlantType;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IamAdminEditUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('partner_profiles');
        Schema::dropIfExists('partner_subscriptions');
        Schema::dropIfExists('subscription_tiers');
        Schema::dropIfExists('unverified_member_profiles');
        Schema::dropIfExists('engineer_profile_plant_type');
        Schema::dropIfExists('engineer_profiles');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('plant_types');
        Schema::dropIfExists('verification_requests');
        Schema::dropIfExists('login_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_engineer_edit_uploads_profile_photo_media_and_keeps_profile_updates(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $plantType = PlantType::create(['name' => 'Hydrogen', 'slug' => 'hydrogen', 'is_active' => true, 'sort_order' => 1]);
        $engineer = $this->createUser('engineer@example.test', [
            'first_name' => 'Eva',
            'last_name' => 'Engineer',
            'role' => 'professional',
            'is_verified' => true,
            'verified_at' => now(),
        ]);
        $profile = EngineerProfile::create(['user_id' => $engineer->id, 'current_company' => 'Old Works', 'experience_years' => 9]);
        DB::table('engineer_profile_plant_type')->insert([
            'engineer_profile_id' => $profile->id,
            'plant_type_id' => $plantType->id,
            'is_primary' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.dashboard.iam.users.update-engineer', $engineer), [
            'account_type' => 'professional',
            'first_name' => 'Eva',
            'last_name' => 'Engineer',
            'email' => 'engineer@example.test',
            'status' => 'active',
            'current_company' => 'New Hydrogen Works',
            'position' => 'Lead Engineer',
            'experience_years' => '11',
            'job_availability' => 'not_looking',
            'plant_type_ids' => [$plantType->id],
            'profile_photo' => $this->validPngUpload('engineer-photo.png'),
        ]);

        $response->assertRedirect(route('admin.dashboard.iam.users.show', $engineer));

        $profile->refresh();
        $media = MediaFile::findOrFail($profile->photo_media_id);

        $this->assertSame('New Hydrogen Works', $profile->current_company);
        $this->assertSame('public', $media->disk);
        $this->assertSame('profile_photo', $media->upload_context);
        $this->assertSame('image', $media->file_category);
        $this->assertSame(EngineerProfile::class, $media->attachable_type);
        $this->assertSame($profile->id, $media->attachable_id);
        $this->assertFalse($media->is_orphan);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_registered_member_edit_binds_photo_to_unverified_member_profile_when_available(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $plantType = PlantType::create(['name' => 'Methanol', 'slug' => 'methanol', 'is_active' => true, 'sort_order' => 1]);
        $member = $this->createUser('member@example.test', [
            'first_name' => 'Mira',
            'last_name' => 'Member',
            'role' => 'unverified_member',
            'is_verified' => false,
        ]);
        $engineerProfile = EngineerProfile::create(['user_id' => $member->id, 'current_institution' => 'Charley Academy', 'experience_years' => 2]);
        $unverifiedProfileId = DB::table('unverified_member_profiles')->insertGetId([
            'user_id' => $member->id,
            'field_of_study' => 'Methanol safety',
            'experience_years' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('engineer_profile_plant_type')->insert([
            'engineer_profile_id' => $engineerProfile->id,
            'plant_type_id' => $plantType->id,
            'is_primary' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.dashboard.iam.users.update-engineer', $member), [
            'account_type' => 'member',
            'first_name' => 'Mira',
            'last_name' => 'Member',
            'email' => 'member@example.test',
            'status' => 'active',
            'current_company' => 'Charley Academy',
            'position' => 'Research Intern',
            'experience_years' => '2',
            'job_availability' => 'open',
            'plant_type_ids' => [$plantType->id],
            'profile_photo' => $this->validPngUpload('member-photo.png'),
        ]);

        $response->assertRedirect(route('admin.dashboard.iam.users.show', $member));

        $unverifiedPhotoId = (int) DB::table('unverified_member_profiles')->where('id', $unverifiedProfileId)->value('photo_media_id');
        $engineerPhotoId = DB::table('engineer_profiles')->where('id', $engineerProfile->id)->value('photo_media_id');
        $media = MediaFile::findOrFail($unverifiedPhotoId);

        $this->assertNull($engineerPhotoId);
        $this->assertSame('unverified_member_profiles', $media->attachable_type);
        $this->assertSame($unverifiedProfileId, $media->attachable_id);
        $this->assertFalse($media->is_orphan);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_partner_edit_uploads_logo_media_and_keeps_partner_updates(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $partner = $this->createUser('partner@example.test', [
            'first_name' => 'Paula',
            'last_name' => 'Partner',
            'role' => 'partner',
            'is_verified' => true,
            'verified_at' => now(),
        ]);
        $tier = $this->createTier();
        $profile = PartnerProfile::create([
            'user_id' => $partner->id,
            'company_name' => 'Old Partner Co',
            'approval_status' => 'approved',
            'layout_template' => 'layout_1',
            'feed_highlight_enabled' => true,
            'subscription_status' => 'inactive',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.dashboard.iam.users.update-partner', $partner), [
            'company_name' => 'New Partner Co',
            'first_name' => 'Paula',
            'last_name' => 'Partner',
            'email' => 'partner@example.test',
            'username' => 'partner-user',
            'approval_status' => 'approved',
            'public_contact_email' => 'contact@partner.example.test',
            'keywords' => 'catalyst, reformer',
            'layout_template' => 'layout_2',
            'feed_highlight_enabled' => '1',
            'subscription_tier_id' => $tier->id,
            'subscription_status' => 'active',
            'status' => 'active',
            'logo_file' => $this->validPngUpload('partner-logo.png'),
        ]);

        $response->assertRedirect(route('admin.dashboard.iam.users.show', $partner));

        $profile->refresh();
        $media = MediaFile::findOrFail($profile->logo_media_id);

        $this->assertSame('New Partner Co', $profile->company_name);
        $this->assertSame('layout_2', $profile->layout_template);
        $this->assertSame('public', $media->disk);
        $this->assertSame('partner_asset', $media->upload_context);
        $this->assertSame('image', $media->file_category);
        $this->assertSame(PartnerProfile::class, $media->attachable_type);
        $this->assertSame($profile->id, $media->attachable_id);
        $this->assertFalse($media->is_orphan);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_edit_uploads_reject_invalid_files_with_field_errors(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $plantType = PlantType::create(['name' => 'SNG', 'slug' => 'sng', 'is_active' => true, 'sort_order' => 1]);
        $engineer = $this->createUser('invalid.engineer@example.test', [
            'first_name' => 'Invalid',
            'last_name' => 'Engineer',
            'role' => 'professional',
            'is_verified' => true,
            'verified_at' => now(),
        ]);
        $profile = EngineerProfile::create(['user_id' => $engineer->id, 'experience_years' => 5]);
        DB::table('engineer_profile_plant_type')->insert([
            'engineer_profile_id' => $profile->id,
            'plant_type_id' => $plantType->id,
            'is_primary' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $engineerResponse = $this->actingAs($admin)
            ->from(route('admin.dashboard.iam.users.edit-engineer', $engineer))
            ->put(route('admin.dashboard.iam.users.update-engineer', $engineer), [
                'account_type' => 'professional',
                'first_name' => 'Invalid',
                'last_name' => 'Engineer',
                'email' => 'invalid.engineer@example.test',
                'status' => 'active',
                'current_company' => 'Invalid Works',
                'position' => 'Engineer',
                'experience_years' => '5',
                'job_availability' => 'not_looking',
                'plant_type_ids' => [$plantType->id],
                'profile_photo' => UploadedFile::fake()->createWithContent('not-image.txt', 'not an image'),
            ]);

        $engineerResponse->assertRedirect(route('admin.dashboard.iam.users.edit-engineer', $engineer));
        $engineerResponse->assertSessionHasErrors('profile_photo');
        $this->assertNull(EngineerProfile::findOrFail($profile->id)->photo_media_id);

        $partner = $this->createUser('invalid.partner@example.test', [
            'first_name' => 'Invalid',
            'last_name' => 'Partner',
            'role' => 'partner',
        ]);
        PartnerProfile::create([
            'user_id' => $partner->id,
            'company_name' => 'Invalid Partner Co',
            'approval_status' => 'pending',
            'layout_template' => 'layout_1',
            'subscription_status' => 'inactive',
        ]);

        $partnerResponse = $this->actingAs($admin)
            ->from(route('admin.dashboard.iam.users.edit-partner', $partner))
            ->put(route('admin.dashboard.iam.users.update-partner', $partner), [
                'company_name' => 'Invalid Partner Co',
                'first_name' => 'Invalid',
                'last_name' => 'Partner',
                'email' => 'invalid.partner@example.test',
                'approval_status' => 'pending',
                'layout_template' => 'layout_1',
                'subscription_status' => 'inactive',
                'status' => 'active',
                'logo_file' => UploadedFile::fake()->createWithContent('bad-logo.txt', 'not an image'),
            ]);

        $partnerResponse->assertRedirect(route('admin.dashboard.iam.users.edit-partner', $partner));
        $partnerResponse->assertSessionHasErrors('logo_file');
        $this->assertNull(PartnerProfile::where('user_id', $partner->id)->firstOrFail()->logo_media_id);
        $this->assertSame(0, MediaFile::count());
    }

    private function validPngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
    }

    /**
     * @param array<string, mixed> $overrides
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
     * @param array<string, mixed> $overrides
     */
    private function createTier(array $overrides = []): SubscriptionTier
    {
        $id = DB::table('subscription_tiers')->insertGetId(array_merge([
            'name' => 'Dynamic Partner',
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
        ], $overrides));

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

        Schema::create('plant_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
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
            $table->string('processing_status')->nullable();
            $table->boolean('is_orphan')->default(false);
            $table->timestamps();
        });

        Schema::create('engineer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->foreignId('photo_media_id')->nullable();
            $table->text('bio')->nullable();
            $table->string('current_company')->nullable();
            $table->string('current_institution')->nullable();
            $table->string('position')->nullable();
            $table->string('field_of_study')->nullable();
            $table->string('plant_name')->nullable();
            $table->integer('experience_years')->nullable();
            $table->text('education')->nullable();
            $table->json('expertise_tags')->nullable();
            $table->json('industry_specialization')->nullable();
            $table->json('searchable_keywords')->nullable();
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('job_availability')->nullable();
            $table->boolean('is_discoverable')->default(true);
            $table->boolean('verification_intent')->default(false);
            $table->timestamps();
        });

        Schema::create('unverified_member_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->foreignId('photo_media_id')->nullable();
            $table->string('field_of_study')->nullable();
            $table->integer('experience_years')->nullable();
            $table->timestamps();
        });

        Schema::create('engineer_profile_plant_type', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('engineer_profile_id')->constrained('engineer_profiles')->cascadeOnDelete();
            $table->foreignId('plant_type_id')->constrained('plant_types')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
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
            $table->timestamps();
        });

        Schema::create('partner_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('company_name');
            $table->foreignId('logo_media_id')->nullable();
            $table->text('overview')->nullable();
            $table->foreignId('active_partner_subscription_id')->nullable();
            $table->foreignId('plant_type_id')->nullable();
            $table->json('keywords')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('website')->nullable();
            $table->smallInteger('founded_year')->nullable();
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
