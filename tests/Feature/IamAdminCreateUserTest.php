<?php

namespace Tests\Feature;

use App\Models\EngineerProfile;
use App\Models\PlantType;
use App\Models\User;
use App\Services\ProfileSearchIndexService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IamAdminCreateUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(ProfileSearchIndexService::class, function ($mock): void {
            $mock->shouldReceive('refresh')->zeroOrMoreTimes();
        });

        $this->createTestSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('verification_requests');
        Schema::dropIfExists('engineer_profile_plant_type');
        Schema::dropIfExists('engineer_profiles');
        Schema::dropIfExists('knowledge_domains');
        Schema::dropIfExists('plant_types');
        Schema::dropIfExists('login_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_admin_created_professional_gets_engineer_profile_and_plant_type_pivots(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $ammonia = PlantType::create(['name' => 'Ammonia', 'slug' => 'ammonia', 'is_active' => true, 'sort_order' => 1]);
        $methanol = PlantType::create(['name' => 'Methanol', 'slug' => 'methanol', 'is_active' => true, 'sort_order' => 2]);

        $response = $this->actingAs($admin)->post(route('admin.dashboard.iam.users.store-engineer'), [
            'account_type' => 'professional',
            'first_name' => 'Process',
            'last_name' => 'Expert',
            'email' => 'process.expert@example.test',
            'current_company' => 'Northgate Energy',
            'position' => 'Process Lead',
            'plant_name' => 'Northgate Ammonia Plant',
            'years_experience' => '12',
            'phone' => '+1 555 000 0000',
            'linkedin_url' => 'https://linkedin.com/in/process-expert',
            'expertise_tags' => 'reformer, synthesis loop',
            'industry_specialization' => 'ammonia, hydrogen',
            'searchable_keywords' => 'process, ammonia',
            'plant_type_ids' => [$ammonia->id, $methanol->id],
            'primary_plant_type_id' => $methanol->id,
        ]);

        $response->assertRedirect();

        $user = User::where('email', 'process.expert@example.test')->firstOrFail();
        $profile = EngineerProfile::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('professional', $user->role);
        $this->assertSame('Northgate Energy', $profile->current_company);
        $this->assertSame('Process Lead', $profile->position);
        $this->assertSame('Northgate Ammonia Plant', $profile->plant_name);
        $this->assertSame(12, $profile->experience_years);
        $this->assertSame(['reformer', 'synthesis loop'], $profile->expertise_tags);
        $this->assertSame(['ammonia', 'hydrogen'], $profile->industry_specialization);
        $this->assertDatabaseHas('engineer_profile_plant_type', [
            'engineer_profile_id' => $profile->id,
            'plant_type_id' => $methanol->id,
            'is_primary' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_created_registered_member_gets_unverified_profile_and_plant_type_pivot(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $hydrogen = PlantType::create(['name' => 'Hydrogen', 'slug' => 'hydrogen', 'is_active' => true, 'sort_order' => 1]);

        $response = $this->actingAs($admin)->post(route('admin.dashboard.iam.users.store-engineer'), [
            'account_type' => 'member',
            'first_name' => 'Registered',
            'last_name' => 'Member',
            'email' => 'registered.member@example.test',
            'current_institution' => 'Technical University',
            'field_of_study' => 'Hydrogen production',
            'years_experience' => '2',
            'linkedin_url' => 'https://linkedin.com/in/registered-member',
            'expertise_tags' => 'student, hydrogen',
            'verification_intent' => '1',
            'plant_type_ids' => [$hydrogen->id],
        ]);

        $response->assertRedirect();

        $user = User::where('email', 'registered.member@example.test')->firstOrFail();
        $profile = EngineerProfile::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('unverified_member', $user->role);
        $this->assertSame('Technical University', $profile->current_institution);
        $this->assertSame('Hydrogen production', $profile->field_of_study);
        $this->assertSame(2, $profile->experience_years);
        $this->assertTrue($profile->verification_intent);
        $this->assertDatabaseHas('engineer_profile_plant_type', [
            'engineer_profile_id' => $profile->id,
            'plant_type_id' => $hydrogen->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_create_engineer_form_uses_two_account_types_and_split_name_fields(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.create-engineer'));

        $response->assertOk()
            ->assertSee('value="member"', false)
            ->assertSee('Registered Member')
            ->assertSee('value="professional"', false)
            ->assertSee('Professional')
            ->assertSee('name="first_name"', false)
            ->assertSee('name="last_name"', false)
            ->assertDontSee('name="full_name"', false)
            ->assertDontSee('Diamond')
            ->assertDontSee('Gold')
            ->assertDontSee('Platinum');
    }

    public function test_create_engineer_technical_area_dropdown_uses_active_domains_for_selected_plant_type(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);
        $ammonia = PlantType::create(['name' => 'Ammonia', 'slug' => 'ammonia', 'is_active' => true, 'sort_order' => 1]);
        $methanol = PlantType::create(['name' => 'Methanol', 'slug' => 'methanol', 'is_active' => true, 'sort_order' => 2]);

        DB::table('knowledge_domains')->insert([
            ['name' => 'Methanol Loop', 'slug' => 'methanol-loop', 'plant_type_id' => $methanol->id, 'is_active' => true, 'sort_order' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Methanol Catalyst', 'slug' => 'methanol-catalyst', 'plant_type_id' => $methanol->id, 'is_active' => true, 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Inactive Methanol', 'slug' => 'inactive-methanol', 'plant_type_id' => $methanol->id, 'is_active' => false, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ammonia Synthesis', 'slug' => 'ammonia-synthesis', 'plant_type_id' => $ammonia->id, 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'General Safety', 'slug' => 'general-safety', 'plant_type_id' => null, 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.create-engineer'));
        $content = $response->getContent();

        $response->assertOk()
            ->assertSee('knowledgeDomainsByPlantType', false)
            ->assertSee('Select a technical area...', false)
            ->assertSee('Select an Industry background Plant Type to show active technical areas.')
            ->assertSee('Methanol Catalyst')
            ->assertSee('Methanol Loop')
            ->assertSee('Ammonia Synthesis')
            ->assertDontSee('Inactive Methanol')
            ->assertDontSee('General Safety');

        $this->assertLessThan(
            strpos($content, 'Methanol Loop'),
            strpos($content, 'Methanol Catalyst')
        );
    }

    public function test_admin_created_engineer_uses_first_and_last_name_fields(): void
    {
        $admin = $this->createUser('admin@example.test', ['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.dashboard.iam.users.store-engineer'), [
            'account_type' => 'member',
            'first_name' => 'Split',
            'last_name' => 'Identity',
            'email' => 'split.identity@example.test',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'split.identity@example.test',
            'first_name' => 'Split',
            'last_name' => 'Identity',
            'role' => 'unverified_member',
        ]);
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

        Schema::create('knowledge_domains', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->foreignId('plant_type_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
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
            $table->json('references')->nullable();
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('job_availability')->nullable();
            $table->integer('reputation_points')->default(0);
            $table->json('reputation_breakdown')->nullable();
            $table->integer('ai_usage_count')->default(0);
            $table->boolean('is_discoverable')->default(true);
            $table->json('privacy_settings')->default('{}');
            $table->json('notification_preferences')->default('{}');
            $table->boolean('verification_intent')->default(false);
            $table->foreignId('verification_document_media_id')->nullable();
            $table->timestamp('verification_renewed_at')->nullable();
            $table->timestamp('renewal_reminder_sent_at')->nullable();
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

        Schema::create('verification_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }
}
