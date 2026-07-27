<?php

namespace Tests\Feature;

use App\Models\EngineerProfile;
use App\Models\MediaFile;
use App\Models\PartnerProfile;
use App\Models\PartnerSubscription;
use App\Models\PlantType;
use App\Models\SubscriptionTier;
use App\Models\User;
use Database\Factories\VerificationRequestFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IamAdminBladeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_iam_listing_hub_with_stats_tabs_table_and_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        $plantType = PlantType::create([
            'name' => 'Process Safety',
            'slug' => 'process-safety',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $activeProfessional = User::factory()->professional()->create([
            'first_name' => 'Ava',
            'last_name' => 'Operator',
            'email' => 'ava.operator@example.test',
            'status' => 'active',
            'created_at' => now()->addDay(),
            'updated_at' => now()->addDay(),
        ]);
        $profile = EngineerProfile::create([
            'user_id' => $activeProfessional->id,
            'industry_specialization' => ['Legacy specialization'],
            'experience_years' => 12,
        ]);
        DB::table('engineer_profile_plant_type')->insert([
            'engineer_profile_id' => $profile->id,
            'plant_type_id' => $plantType->id,
            'is_primary' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pendingMember = User::factory()->unverified()->create([
            'first_name' => 'Pending',
            'last_name' => 'Member',
            'email' => 'pending.member@example.test',
            'status' => 'active',
        ]);
        VerificationRequestFactory::new()->create([
            'user_id' => $pendingMember->id,
            'status' => 'pending',
        ]);

        User::factory()->frozen()->create([
            'first_name' => 'Frozen',
            'last_name' => 'Member',
            'email' => 'frozen.member@example.test',
            'role' => 'professional',
            'is_verified' => true,
        ]);

        User::factory()->count(11)->professional()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers'));

        $response->assertOk()
            ->assertSee('Total Users')
            ->assertSee('Professionals')
            ->assertSee('Pending Verification')
            ->assertSee('Suspended / Frozen')
            ->assertSee('All Users')
            ->assertSee('Ava Operator')
            ->assertSee('ava.operator@example.test')
            ->assertSee('Process Safety')
            ->assertSee('Industry Professional')
            ->assertSee('View profile')
            ->assertSee('Freeze account')
            ->assertSee('Showing')
            ->assertSee('page-btn');
    }

    public function test_sidebar_shows_member_management_groups_without_approval_queue(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers'));

        $response->assertOk()
            ->assertSee('Member Management')
            ->assertSee('Engineers')
            ->assertSee('Partners')
            ->assertSee('Administrators')
            ->assertDontSee('Approval Queue');
    }

    public function test_administrator_listing_searches_internal_operators_only(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create([
            'first_name' => 'Searchable',
            'last_name' => 'Admin',
            'email' => 'searchable.admin@example.test',
            'status' => 'active',
        ]);
        User::factory()->admin()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Moderator',
            'email' => 'hidden.moderator@example.test',
            'role' => 'moderator',
            'status' => 'active',
        ]);
        User::factory()->professional()->create([
            'first_name' => 'Searchable',
            'last_name' => 'Engineer',
            'email' => 'searchable.engineer@example.test',
            'status' => 'active',
        ]);
        User::factory()->create([
            'first_name' => 'Searchable',
            'last_name' => 'Partner',
            'email' => 'searchable.partner@example.test',
            'role' => 'partner',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', [
            'keyword' => 'searchable',
        ]));

        $response->assertOk()
            ->assertSee('Administrator Management')
            ->assertSee('Searchable Admin')
            ->assertSee('searchable.admin@example.test')
            ->assertDontSee('Hidden Moderator')
            ->assertDontSee('searchable.engineer@example.test')
            ->assertDontSee('searchable.partner@example.test')
            ->assertDontSee('name="role"', false)
            ->assertDontSee('name="plant_type_id"', false)
            ->assertDontSee('Specialization');
    }

    public function test_engineer_management_searches_profile_fields_and_filters_account_type_and_status(): void
    {
        $admin = User::factory()->admin()->create();
        $plantType = PlantType::create([
            'name' => 'Hydrogen',
            'slug' => 'hydrogen-filter-task',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $emma = User::factory()->unverified()->create([
            'first_name' => 'Emma',
            'last_name' => 'Intern',
            'email' => 'emma.intern@example.test',
            'status' => 'active',
        ]);
        $emmaProfile = EngineerProfile::create([
            'user_id' => $emma->id,
            'current_institution' => 'Charley Academy',
            'field_of_study' => 'Hydrogen Safety Internship',
            'searchable_keywords' => ['internship', 'hydrogen'],
            'experience_years' => 1,
        ]);
        DB::table('engineer_profile_plant_type')->insert([
            'engineer_profile_id' => $emmaProfile->id,
            'plant_type_id' => $plantType->id,
            'is_primary' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $hidden = User::factory()->professional()->create([
            'first_name' => 'Senior',
            'last_name' => 'Engineer',
            'email' => 'senior.engineer@example.test',
            'status' => 'active',
        ]);
        EngineerProfile::create([
            'user_id' => $hidden->id,
            'current_company' => 'Methanol Works',
            'position' => 'Lead Engineer',
            'plant_name' => 'Alpha Plant',
            'expertise_tags' => ['reforming'],
            'experience_years' => 12,
        ]);

        $searchResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers', [
            'keyword' => 'Emma Intern',
        ]));
        $profileSearchResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers', [
            'keyword' => 'hydrogen safety',
        ]));
        $accountTypeResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers', [
            'account_type' => 'registered',
        ]));
        $pendingStatusResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers', [
            'status' => 'pending',
        ]));

        $searchResponse->assertOk()
            ->assertSee('Emma Intern')
            ->assertSee('emma.intern@example.test')
            ->assertDontSee('Senior Engineer')
            ->assertSee('name="account_type"', false)
            ->assertSee('name="status"', false);
        $profileSearchResponse->assertOk()
            ->assertSee('Emma Intern')
            ->assertSee('emma.intern@example.test')
            ->assertDontSee('Senior Engineer');
        $accountTypeResponse->assertOk()
            ->assertSee('Emma Intern')
            ->assertDontSee('Senior Engineer');
        $pendingStatusResponse->assertOk()
            ->assertSee('Emma Intern')
            ->assertDontSee('Senior Engineer');
    }

    public function test_legacy_sidebar_role_links_select_member_groups_without_role_filter_control(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'first_name' => 'Sidebar',
            'last_name' => 'Partner',
            'email' => 'sidebar.partner@example.test',
            'role' => 'partner',
            'status' => 'active',
        ]);
        User::factory()->professional()->create([
            'first_name' => 'Sidebar',
            'last_name' => 'Engineer',
            'email' => 'sidebar.engineer@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.partners'));

        $response->assertOk()
            ->assertSee('Partner Management')
            ->assertSee('Sidebar Partner')
            ->assertDontSee('Sidebar Engineer')
            ->assertDontSee('name="role"', false);
    }

    public function test_engineer_plant_type_filter_uses_profile_pivot_mapping(): void
    {
        $admin = User::factory()->admin()->create();
        $matchingPlantType = PlantType::create([
            'name' => 'Hydrogen',
            'slug' => 'hydrogen',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $otherPlantType = PlantType::create([
            'name' => 'Ammonia',
            'slug' => 'ammonia',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $matchingUser = User::factory()->professional()->create([
            'first_name' => 'Hydrogen',
            'last_name' => 'Engineer',
            'email' => 'hydrogen.engineer@example.test',
            'status' => 'active',
        ]);
        $hiddenUser = User::factory()->professional()->create([
            'first_name' => 'Ammonia',
            'last_name' => 'Engineer',
            'email' => 'ammonia.engineer@example.test',
            'status' => 'active',
        ]);
        $matchingProfile = EngineerProfile::create(['user_id' => $matchingUser->id, 'experience_years' => 8]);
        $hiddenProfile = EngineerProfile::create(['user_id' => $hiddenUser->id, 'experience_years' => 6]);

        DB::table('engineer_profile_plant_type')->insert([
            [
                'engineer_profile_id' => $matchingProfile->id,
                'plant_type_id' => $matchingPlantType->id,
                'is_primary' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'engineer_profile_id' => $hiddenProfile->id,
                'plant_type_id' => $otherPlantType->id,
                'is_primary' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers', [
            'plant_type_id' => $matchingPlantType->id,
        ]));

        $response->assertOk()
            ->assertSee('Hydrogen Engineer')
            ->assertSee('Hydrogen')
            ->assertDontSee('Ammonia Engineer')
            ->assertDontSee('ammonia.engineer@example.test');
    }

    public function test_partner_plant_type_filter_uses_partner_profile_mapping(): void
    {
        $admin = User::factory()->admin()->create();
        $matchingPlantType = PlantType::create([
            'name' => 'Methanol',
            'slug' => 'methanol',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $otherPlantType = PlantType::create([
            'name' => 'Refinery',
            'slug' => 'refinery',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $matchingUser = User::factory()->create([
            'first_name' => 'Methanol',
            'last_name' => 'Partner',
            'email' => 'methanol.partner@example.test',
            'role' => 'partner',
            'status' => 'active',
        ]);
        $hiddenUser = User::factory()->create([
            'first_name' => 'Refinery',
            'last_name' => 'Partner',
            'email' => 'refinery.partner@example.test',
            'role' => 'partner',
            'status' => 'active',
        ]);
        PartnerProfile::factory()->approved()->create([
            'user_id' => $matchingUser->id,
            'company_name' => 'Methanol Partner Co',
            'plant_type_id' => $matchingPlantType->id,
        ]);
        PartnerProfile::factory()->approved()->create([
            'user_id' => $hiddenUser->id,
            'company_name' => 'Refinery Partner Co',
            'plant_type_id' => $otherPlantType->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.partners', [
            'plant_type_id' => $matchingPlantType->id,
        ]));

        $response->assertOk()
            ->assertSee('Partner Management')
            ->assertSee('Methanol Partner')
            ->assertSee('Methanol')
            ->assertDontSee('Refinery Partner')
            ->assertDontSee('refinery.partner@example.test');
    }

    public function test_pending_tabs_are_scoped_to_the_selected_member_group(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingEngineer = User::factory()->professional()->create([
            'first_name' => 'Review',
            'last_name' => 'Engineer',
            'email' => 'review.engineer@example.test',
            'status' => 'active',
        ]);
        VerificationRequestFactory::new()->create([
            'user_id' => $pendingEngineer->id,
            'status' => 'pending',
        ]);
        $pendingPartner = User::factory()->create([
            'first_name' => 'Review',
            'last_name' => 'Partner',
            'email' => 'review.partner@example.test',
            'role' => 'partner',
            'status' => 'active',
        ]);
        PartnerProfile::factory()->create([
            'user_id' => $pendingPartner->id,
            'company_name' => 'Review Partner',
            'approval_status' => 'pending',
        ]);

        $engineerResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers', ['tab' => 'pending']));
        $partnerResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.partners', [
            'tab' => 'pending',
        ]));

        $engineerResponse->assertOk()
            ->assertSee('Engineer Management')
            ->assertSee('Review Engineer')
            ->assertDontSee('Review Partner');
        $partnerResponse->assertOk()
            ->assertSee('Partner Management')
            ->assertSee('Review Partner')
            ->assertDontSee('Review Engineer');
    }

    public function test_frozen_tab_is_based_on_user_status(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->frozen()->create([
            'first_name' => 'Frozen',
            'last_name' => 'Expert',
            'email' => 'frozen.expert@example.test',
            'role' => 'professional',
            'is_verified' => true,
        ]);
        User::factory()->professional()->create([
            'first_name' => 'Active',
            'last_name' => 'Expert',
            'email' => 'active.expert@example.test',
            'status' => 'active',
            'locked_until' => null,
            'self_frozen_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers', ['tab' => 'frozen']));

        $response->assertOk()
            ->assertSee('Frozen Expert')
            ->assertSee('Frozen')
            ->assertDontSee('active.expert@example.test');
    }

    public function test_missing_engineer_plant_type_mapping_has_clear_fallback_and_no_fake_filter_match(): void
    {
        $admin = User::factory()->admin()->create();
        $plantType = PlantType::create([
            'name' => 'SNG',
            'slug' => 'sng',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        User::factory()->professional()->create([
            'first_name' => 'Unmapped',
            'last_name' => 'Engineer',
            'email' => 'unmapped.engineer@example.test',
            'status' => 'active',
        ]);

        $unfilteredResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers'));
        $filteredResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers', [
            'plant_type_id' => $plantType->id,
        ]));

        $unfilteredResponse->assertOk()
            ->assertSee('Unmapped Engineer')
            ->assertSee('No plant type');
        $filteredResponse->assertOk()
            ->assertDontSee('Unmapped Engineer')
            ->assertDontSee('unmapped.engineer@example.test');
    }

    public function test_administrators_view_hides_member_profile_columns_and_filters(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create([
            'first_name' => 'Security',
            'last_name' => 'Admin',
            'email' => 'security.admin@example.test',
            'status' => 'active',
            'mfa_enabled' => true,
            'login_attempts' => 2,
        ]);
        User::factory()->professional()->create([
            'first_name' => 'Visible',
            'last_name' => 'Engineer',
            'email' => 'visible.engineer@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users'));

        $response->assertOk()
            ->assertSee('Administrator Management')
            ->assertSee('Security Admin')
            ->assertSee('MFA enabled')
            ->assertSee('2 failed logins')
            ->assertSee('<th>Security</th>', false)
            ->assertDontSee('Visible Engineer')
            ->assertDontSee('for="plant_type_id"', false)
            ->assertDontSee('<th>Plant Type</th>', false)
            ->assertDontSee('<th>Experience</th>', false);
    }

    public function test_iam_user_detail_routes_to_role_specific_templates(): void
    {
        $admin = User::factory()->admin()->create();
        $professional = User::factory()->professional()->create([
            'first_name' => 'Role',
            'last_name' => 'Engineer',
            'email' => 'role.engineer@example.test',
        ]);
        EngineerProfile::create([
            'user_id' => $professional->id,
            'current_company' => 'Charley Process Lab',
            'position' => 'Senior Engineer',
            'industry_specialization' => ['Hydrogen'],
            'experience_years' => 11,
        ]);
        $registeredMember = User::factory()->unverified()->create([
            'first_name' => 'Registered',
            'last_name' => 'Member',
            'email' => 'registered.member@example.test',
            'status' => 'active',
        ]);
        EngineerProfile::create([
            'user_id' => $registeredMember->id,
            'current_institution' => 'Charley Institute',
            'field_of_study' => 'Process Design',
            'experience_years' => 2,
        ]);
        $partner = User::factory()->create([
            'first_name' => 'Partner',
            'last_name' => 'Owner',
            'email' => 'partner.owner@example.test',
            'role' => 'partner',
            'status' => 'active',
        ]);
        PartnerProfile::factory()->approved()->create([
            'user_id' => $partner->id,
            'company_name' => 'Partner Detail Co',
            'contact_email' => 'contact@partner-detail.test',
            'keywords' => ['Catalyst', 'Safety'],
        ]);
        $administrator = User::factory()->admin()->create([
            'first_name' => 'Detail',
            'last_name' => 'Admin',
            'email' => 'detail.admin@example.test',
        ]);
        $moderator = User::factory()->admin()->create([
            'first_name' => 'Detail',
            'last_name' => 'Moderator',
            'email' => 'detail.moderator@example.test',
            'role' => 'moderator',
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard.iam.users.show', $professional))
            ->assertOk()
            ->assertViewIs('iam.users.show-engineer')
            ->assertSee('Identity & Professional Profile')
            ->assertSee('Charley Process Lab')
            ->assertSee('Account Controls')
            ->assertSee('TODO-safe static boundary')
            ->assertSee('Member Management');

        $this->actingAs($admin)->get(route('admin.dashboard.iam.users.show', $registeredMember))
            ->assertOk()
            ->assertViewIs('iam.users.show-engineer')
            ->assertSee('Registered member')
            ->assertSee('Charley Institute')
            ->assertSee('Account Controls');

        $this->actingAs($admin)->get(route('admin.dashboard.iam.users.show', $partner))
            ->assertOk()
            ->assertViewIs('iam.users.show-partner')
            ->assertSee('Legal Identity')
            ->assertSee('Partner Detail Co')
            ->assertSee('Partner Display')
            ->assertSee('TODO-safe display boundary')
            ->assertSee('Activate partner');

        foreach ([$administrator, $moderator] as $operator) {
            $this->actingAs($admin)->get(route('admin.dashboard.iam.users.show', $operator))
                ->assertOk()
                ->assertViewIs('iam.users.show-admin')
                ->assertSee('Internal Identity')
                ->assertSee('Review Workload')
                ->assertSee('TODO-safe workload boundary')
                ->assertSee('Activate operator');
        }
    }

    public function test_partner_detail_preserves_layout_links_modals_and_dynamic_media(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $plantType = PlantType::create([
            'name' => 'Hydrogen',
            'slug' => 'hydrogen-partner-detail',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $tierId = DB::table('subscription_tiers')->insertGetId([
            'code' => 'diamond-dynamic',
            'name' => 'diamond-dynamic',
            'display_name' => 'Diamond Dynamic',
            'description' => 'Dynamic partner detail test tier',
            'monthly_price' => 1200,
            'billing_cycle' => 'yearly',
            'duration_days' => 365,
            'sort_order' => 1,
            'is_public' => true,
            'is_active' => true,
            'ai_monthly_limit' => -1,
            'announcement_frequency' => 'monthly',
            'announcement_limit' => 12,
            'can_host_webinar' => true,
            'can_initiate_message' => true,
            'can_create_poll' => true,
            'can_publish_events' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tier = SubscriptionTier::findOrFail($tierId);
        $partner = User::factory()->create([
            'first_name' => 'Dynamic',
            'last_name' => 'Owner',
            'email' => 'dynamic.owner@example.test',
            'role' => 'partner',
            'status' => 'active',
            'created_at' => now()->subYear(),
        ]);
        $subscription = PartnerSubscription::create([
            'user_id' => $partner->id,
            'tier_id' => $tier->id,
            'status' => 'active',
            'auto_renew' => true,
            'approved_by' => $admin->id,
            'approved_at' => now()->subMonth(),
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(11),
        ]);
        $logo = MediaFile::create([
            'uploader_id' => $admin->id,
            'disk' => 'public',
            'path' => 'partner-logos/dynamic-logo.svg',
            'original_name' => 'dynamic-logo.svg',
            'mime_type' => 'image/svg+xml',
            'size' => 512,
            'upload_context' => 'partner_asset',
            'file_category' => 'image',
            'sort_order' => 0,
            'is_watermarked' => false,
            'processing_status' => 'processed',
            'is_orphan' => false,
        ]);
        $profile = PartnerProfile::factory()->approved()->create([
            'user_id' => $partner->id,
            'company_name' => 'Dynamic Detail Partners',
            'logo_media_id' => $logo->id,
            'overview' => 'Dynamic overview preserves the partner detail source design.',
            'active_partner_subscription_id' => $subscription->id,
            'plant_type_id' => $plantType->id,
            'keywords' => ['Catalyst Safety', 'Hydrogen Services'],
            'contact_email' => 'contact@dynamic-detail.test',
            'phone' => '+31 10 555 0101',
            'country' => 'Netherlands',
            'website' => 'https://dynamic-detail.example.test',
            'founded_year' => 2012,
            'subscription_status' => 'active',
            'subscription_expires_at' => $subscription->ends_at,
        ]);
        $logo->forceFill([
            'attachable_type' => PartnerProfile::class,
            'attachable_id' => $profile->id,
        ])->save();

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.show', $partner));

        $response->assertOk()
            ->assertViewIs('iam.users.show-partner')
            ->assertSee('class="profile-head"', false)
            ->assertSee('class="profile-head-row"', false)
            ->assertSee('class="detail-tabs"', false)
            ->assertSee('id="panel-overview"', false)
            ->assertSee('id="panel-billing"', false)
            ->assertSee('id="panel-connections"', false)
            ->assertSee('id="panel-audit"', false)
            ->assertSee('Overview')
            ->assertSee('Subscription &amp; Billing', false)
            ->assertSee('Connections')
            ->assertSee('Audit Log')
            ->assertSee(route('admin.dashboard.iam.users.partners'), false)
            ->assertSee(route('admin.dashboard.iam.users.edit-partner', $partner), false)
            ->assertSee("onclick=\"openDetailModal('freeze')\"", false)
            ->assertSee("onclick=\"openDetailModal('renew')\"", false)
            ->assertSee("onclick=\"openDetailModal('changeTier')\"", false)
            ->assertSee("onclick=\"openDetailModal('payment')\"", false)
            ->assertSee("onclick=\"openDetailModal('sendInvoice')\"", false)
            ->assertSee('id="freezeModal"', false)
            ->assertSee('id="renewModal"', false)
            ->assertSee('id="changeTierModal"', false)
            ->assertSee('id="paymentModal"', false)
            ->assertSee('id="sendInvoiceModal"', false)
            ->assertSee('class="profile-logo-img"', false)
            ->assertSee('/storage/partner-logos/dynamic-logo.svg', false)
            ->assertSee('alt="Dynamic Detail Partners logo"', false)
            ->assertSee('Dynamic Detail Partners')
            ->assertSee('Dynamic overview preserves the partner detail source design.')
            ->assertSee('Diamond Dynamic')
            ->assertSee('Netherlands')
            ->assertSee('https://dynamic-detail.example.test')
            ->assertSee('contact@dynamic-detail.test')
            ->assertSee('Catalyst Safety')
            ->assertSee('TODO-safe modal boundary');

        $fallbackPartner = User::factory()->create([
            'first_name' => 'Fallback',
            'last_name' => 'Owner',
            'email' => 'fallback.owner@example.test',
            'role' => 'partner',
            'status' => 'active',
        ]);
        PartnerProfile::factory()->approved()->create([
            'user_id' => $fallbackPartner->id,
            'company_name' => 'Fallback Partner',
            'logo_media_id' => null,
            'contact_email' => 'fallback@example.test',
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard.iam.users.show', $fallbackPartner))
            ->assertOk()
            ->assertViewIs('iam.users.show-partner')
            ->assertSee('<div class="profile-logo">FP</div>', false)
            ->assertDontSee('class="profile-logo-img"', false);
    }

    public function test_iam_listing_detail_links_resolve_to_split_detail_views(): void
    {
        $admin = User::factory()->admin()->create();
        $engineer = User::factory()->professional()->create([
            'first_name' => 'Linked',
            'last_name' => 'Engineer',
            'email' => 'linked.engineer@example.test',
            'status' => 'active',
        ]);
        EngineerProfile::create([
            'user_id' => $engineer->id,
            'experience_years' => 6,
        ]);
        $detailUrl = route('admin.dashboard.iam.users.show', $engineer);

        $this->actingAs($admin)->get(route('admin.dashboard.iam.users.engineers'))
            ->assertOk()
            ->assertSee('Linked Engineer')
            ->assertSee($detailUrl, false)
            ->assertSee('Member Management');

        $this->actingAs($admin)->get($detailUrl)
            ->assertOk()
            ->assertViewIs('iam.users.show-engineer')
            ->assertSee('Identity & Professional Profile');
    }

    public function test_admin_can_open_self_profile_from_rebuild_topbar_links(): void
    {
        $admin = User::factory()->admin()->create([
            'first_name' => 'Topbar',
            'last_name' => 'Admin',
            'email' => 'topbar.admin@example.test',
            'username' => 'topbar.admin',
            'status' => 'active',
            'mfa_enabled' => true,
            'last_login_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users.admin-profile'));

        $response->assertOk()
            ->assertViewIs('iam.users.admin-profile')
            ->assertSee('My Profile &amp; Settings', false)
            ->assertSee('Topbar Admin')
            ->assertSee('topbar.admin@example.test')
            ->assertSee('topbar.admin')
            ->assertSee('Password &amp; two-factor authentication', false)
            ->assertSee('TODO-safe boundary')
            ->assertSee(route('admin.dashboard.iam.users.admin-profile'), false);
    }

    public function test_regular_user_is_blocked_by_rbac(): void
    {
        $member = User::factory()->professional()->create();

        $this->actingAs($member)
            ->get(route('admin.dashboard.iam.users'))
            ->assertForbidden();
    }
}
