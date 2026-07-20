<?php

namespace Tests\Feature;

use App\Models\EngineerProfile;
use App\Models\PartnerProfile;
use App\Models\PlantType;
use App\Models\User;
use Database\Factories\VerificationRequestFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users'));

        $response->assertOk()
            ->assertSee('Total Users')
            ->assertSee('Active Members')
            ->assertSee('Pending Approvals')
            ->assertSee('Frozen &amp; Suspended', false)
            ->assertSee('User List')
            ->assertSee('Ava Operator')
            ->assertSee('ava.operator@example.test')
            ->assertSee('Process Safety')
            ->assertSee('12 years')
            ->assertSee('View details')
            ->assertSee('Freeze or suspend')
            ->assertSee('Showing')
            ->assertSee('page-link');
    }

    public function test_sidebar_shows_member_management_groups_without_approval_queue(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users'));

        $response->assertOk()
            ->assertSee('Member Management')
            ->assertSee('Engineers')
            ->assertSee('Partners')
            ->assertSee('Administrators')
            ->assertDontSee('Approval Queue');
    }

    public function test_admin_can_search_by_name_or_email_without_role_filter(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->professional()->create([
            'first_name' => 'Searchable',
            'last_name' => 'Expert',
            'email' => 'searchable.expert@example.test',
            'role' => 'professional',
            'status' => 'active',
        ]);
        User::factory()->professional()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Engineer',
            'email' => 'hidden.engineer@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', [
            'keyword' => 'searchable',
        ]));

        $response->assertOk()
            ->assertSee('Searchable Expert')
            ->assertSee('searchable.expert@example.test')
            ->assertDontSee('Hidden Engineer')
            ->assertDontSee('hidden.engineer@example.test')
            ->assertDontSee('name="role"', false)
            ->assertDontSee('All roles', false)
            ->assertDontSee('Specialization');
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

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', ['role' => 'partner']));

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

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', [
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

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', [
            'member_view' => 'partners',
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
            'approval_status' => 'pending',
        ]);

        $engineerResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', ['tab' => 'pending']));
        $partnerResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', [
            'member_view' => 'partners',
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

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', ['tab' => 'frozen']));

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

        $unfilteredResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users'));
        $filteredResponse = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', [
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

        $response = $this->actingAs($admin)->get(route('admin.dashboard.iam.users', [
            'member_view' => 'administrators',
        ]));

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

    public function test_regular_user_is_blocked_by_rbac(): void
    {
        $member = User::factory()->professional()->create();

        $this->actingAs($member)
            ->get(route('admin.dashboard.iam.users'))
            ->assertForbidden();
    }
}


