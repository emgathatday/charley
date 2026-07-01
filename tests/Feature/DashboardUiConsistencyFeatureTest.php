<?php

namespace Tests\Feature;

use App\Models\PlantType;
use App\Models\User;
use Database\Seeders\DemoPartnerDashboardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardUiConsistencyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_pages_require_admin_access(): void
    {
        $this->get('/dashboard/plant-types')->assertRedirect('/login');
        $this->get('/dashboard/partner-profiles')->assertRedirect('/login');
        $this->get('/dashboard/subscriptions')->assertRedirect('/login');

        $user = User::factory()->professional()->create();

        $this->actingAs($user)->get('/dashboard/plant-types')->assertForbidden();
        $this->actingAs($user)->get('/dashboard/partner-profiles')->assertForbidden();
        $this->actingAs($user)->get('/dashboard/subscriptions')->assertForbidden();
    }

    public function test_admin_dashboard_primary_pages_render_adminlte_navigation_and_content(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DemoPartnerDashboardSeeder::class);

        $this->actingAs($admin)
            ->get('/dashboard/plant-types')
            ->assertOk()
            ->assertSee('app-wrapper', false)
            ->assertSee('app-sidebar', false)
            ->assertDontSee('content-wrapper', false)
            ->assertSee('Plant Types')
            ->assertSee('Partner Profiles')
            ->assertSee('Subscriptions')
            ->assertSee('table-responsive', false)
            ->assertSee('breadcrumb', false)
            ->assertSee('nav-link active', false);

        $this->actingAs($admin)
            ->get('/dashboard/partner-profiles?approval_status=approved')
            ->assertOk()
            ->assertSee('app-sidebar', false)
            ->assertSee('Partner Profiles')
            ->assertSee('Plant Types')
            ->assertSee('Subscriptions')
            ->assertSee('Partner Queue')
            ->assertSee('table-responsive', false)
            ->assertSee('Charley Demo Partner Co.')
            ->assertSee('nav-link active', false);

        $this->actingAs($admin)
            ->get('/dashboard/subscriptions?payment_status=pending')
            ->assertOk()
            ->assertSee('app-sidebar', false)
            ->assertSee('Partner Profiles')
            ->assertSee('Plant Types')
            ->assertSee('Subscriptions')
            ->assertSee('Payments')
            ->assertSee('Subscription payments')
            ->assertSee('table-responsive', false)
            ->assertSee('nav-link active', false);
    }

    public function test_plant_type_forms_use_consistent_adminlte_cards_and_responsive_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $plantType = PlantType::query()->create([
            'name' => 'Ammonia',
            'slug' => 'ammonia-plant',
            'description' => 'Ammonia plant.',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard/plant-types/create')
            ->assertOk()
            ->assertSee('card card-outline card-primary', false)
            ->assertSee('form-control', false)
            ->assertSee('form-select', false)
            ->assertSee('row g-3', false)
            ->assertSee('breadcrumb', false)
            ->assertSee('Create Plant Type');

        $this->actingAs($admin)
            ->get("/dashboard/plant-types/{$plantType->id}/edit")
            ->assertOk()
            ->assertSee('card card-outline card-primary', false)
            ->assertSee('form-control', false)
            ->assertSee('form-select', false)
            ->assertSee('row g-3', false)
            ->assertSee('Delete')
            ->assertSee('Edit Plant Type');
    }

    public function test_subscription_sidebar_active_states_match_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seed(DemoPartnerDashboardSeeder::class);

        $this->actingAs($admin)
            ->get('/dashboard/subscriptions?payment_status=pending')
            ->assertOk()
            ->assertSee('payment_status=pending', false)
            ->assertSee('Payments')
            ->assertSee('nav-link active', false);

        $this->actingAs($admin)
            ->get('/dashboard/subscriptions?quota_period='.now()->format('Y-m'))
            ->assertOk()
            ->assertSee('quota_period='.now()->format('Y-m'), false)
            ->assertSee('Quotas')
            ->assertSee('nav-link active', false);
    }
}
