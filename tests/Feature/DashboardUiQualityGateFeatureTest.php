<?php

namespace Tests\Feature;

use App\Models\MemberSubscriptionPlan;
use App\Models\PartnerProfile;
use App\Models\PlantType;
use App\Models\SubscriptionTier;
use App\Models\User;
use Database\Seeders\DemoPartnerDashboardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardUiQualityGateFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_ui_gate_covers_index_create_edit_and_show_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DemoPartnerDashboardSeeder::class);

        $plantType = PlantType::query()->firstOrFail();
        $partnerProfile = PartnerProfile::query()->firstOrFail();
        $tier = SubscriptionTier::query()->firstOrFail();
        $plan = MemberSubscriptionPlan::query()->firstOrFail();

        $routes = [
            ['/dashboard/plant-types', 'Plant Types', 'Plant Type List', 'table-responsive'],
            ['/dashboard/plant-types/create', 'Plant Types', 'Plant Type Details', 'form-control'],
            ["/dashboard/plant-types/{$plantType->id}/edit", 'Plant Types', $plantType->name, 'form-control'],
            ['/dashboard/partner-profiles', 'Partner Profiles', 'Partner Queue', 'table-responsive'],
            ['/dashboard/partner-profiles/create', 'Partner Profiles', 'Partner Profile', 'form-control'],
            ["/dashboard/partner-profiles/{$partnerProfile->id}", 'Partner Profiles', $partnerProfile->company_name, 'card'],
            ["/dashboard/partner-profiles/{$partnerProfile->id}/edit", 'Partner Profiles', $partnerProfile->company_name, 'form-control'],
            ['/dashboard/subscriptions', 'Subscriptions', 'Partner tiers', 'table-responsive'],
            ['/dashboard/subscriptions/tiers/create', 'Subscriptions', 'Tier Details', 'form-select'],
            ["/dashboard/subscriptions/tiers/{$tier->id}/edit", 'Subscriptions', ucfirst($tier->name), 'form-select'],
            ['/dashboard/subscriptions/member-plans/create', 'Subscriptions', 'Plan Details', 'form-control'],
            ["/dashboard/subscriptions/member-plans/{$plan->id}/edit", 'Subscriptions', $plan->display_name, 'form-control'],
        ];

        foreach ($routes as [$url, $activeMenu, $content, $uiClass]) {
            $this->assertDashboardShell(
                $this->actingAs($admin)->get($url),
                $activeMenu,
                $content,
                $uiClass
            );
        }
    }

    public function test_dashboard_ui_gate_checks_filter_links_active_states_and_responsive_tables(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DemoPartnerDashboardSeeder::class);

        $this->assertDashboardShell(
            $this->actingAs($admin)->get('/dashboard/partner-profiles?approval_status=pending'),
            'Partner Profiles',
            'Pending Approval',
            'table-responsive'
        )->assertSee('approval_status=pending', false);

        $this->assertDashboardShell(
            $this->actingAs($admin)->get('/dashboard/subscriptions?payment_status=pending'),
            'Subscriptions',
            'Payments',
            'table-responsive'
        )->assertSee('payment_status=pending', false);

        $this->assertDashboardShell(
            $this->actingAs($admin)->get('/dashboard/subscriptions?quota_period='.now()->format('Y-m')),
            'Subscriptions',
            'Quotas',
            'table-responsive'
        )->assertSee('quota_period='.now()->format('Y-m'), false);
    }

    public function test_dashboard_ui_gate_checks_validation_errors_render_on_forms(): void
    {
        $admin = User::factory()->admin()->create();
        SubscriptionTier::factory()->create(['name' => 'gold']);
        MemberSubscriptionPlan::factory()->create(['name' => 'professional']);
        PlantType::query()->create([
            'name' => 'Ammonia',
            'slug' => 'ammonia-plant',
            'description' => 'Ammonia plant.',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->followingRedirects()
            ->actingAs($admin)
            ->from('/dashboard/plant-types/create')
            ->post('/dashboard/plant-types', [
                'name' => 'Ammonia',
                'slug' => 'ammonia-plant',
                'is_active' => 'bad',
                'sort_order' => '-1',
            ])
            ->assertOk()
            ->assertSee('is-invalid', false)
            ->assertSee('invalid-feedback', false);

        $this->followingRedirects()
            ->actingAs($admin)
            ->from('/dashboard/subscriptions/tiers/create')
            ->post('/dashboard/subscriptions/tiers', [
                'name' => 'gold',
                'monthly_price' => '-1',
                'ai_monthly_limit' => '-2',
                'announcement_frequency' => 'daily',
                'announcement_limit' => '-1',
                'is_active' => 'bad',
            ])
            ->assertOk()
            ->assertSee('is-invalid', false)
            ->assertSee('invalid-feedback', false);

        $this->followingRedirects()
            ->actingAs($admin)
            ->from('/dashboard/subscriptions/member-plans/create')
            ->post('/dashboard/subscriptions/member-plans', [
                'name' => 'professional',
                'display_name' => '',
                'monthly_price' => '-1',
                'ai_monthly_limit' => '-2',
                'is_active' => 'bad',
            ])
            ->assertOk()
            ->assertSee('is-invalid', false)
            ->assertSee('invalid-feedback', false);
    }

    public function test_dashboard_ui_gate_enforces_admin_only_access(): void
    {
        $this->get('/dashboard/plant-types')->assertRedirect('/login');
        $this->get('/dashboard/partner-profiles')->assertRedirect('/login');
        $this->get('/dashboard/subscriptions')->assertRedirect('/login');

        $user = User::factory()->professional()->create();

        $this->actingAs($user)->get('/dashboard/plant-types')->assertForbidden();
        $this->actingAs($user)->get('/dashboard/partner-profiles')->assertForbidden();
        $this->actingAs($user)->get('/dashboard/subscriptions')->assertForbidden();
    }

    private function assertDashboardShell(TestResponse $response, string $activeMenu, string $content, string $uiClass): TestResponse
    {
        $response
            ->assertOk()
            ->assertSee($activeMenu)
            ->assertSee($content)
            ->assertSee($uiClass, false)
            ->assertSee('container-fluid', false)
            ->assertSee('nav-link active', false)
            ->assertDontSee('Undefined variable')
            ->assertDontSee('Exception');

        $html = $response->getContent();

        $this->assertTrue(
            str_contains($html, 'app-sidebar') || str_contains($html, 'main-sidebar'),
            'Dashboard page must render a sidebar.'
        );

        $this->assertTrue(
            str_contains($html, 'app-main') || str_contains($html, 'content-wrapper'),
            'Dashboard page must render the AdminLTE content wrapper.'
        );

        $this->assertTrue(
            str_contains($html, 'row') || str_contains($html, 'table-responsive') || str_contains($html, 'flex-md-row'),
            'Dashboard page must include responsive layout classes.'
        );

        return $response;
    }
}
