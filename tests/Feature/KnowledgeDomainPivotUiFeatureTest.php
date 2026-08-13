<?php

namespace Tests\Feature;

use App\Models\KnowledgeDomain;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KnowledgeDomainPivotUiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pivot_schema_and_legacy_backfill_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('knowledge_domain_plant_type', [
            'id',
            'knowledge_domain_id',
            'plant_type_id',
            'is_primary',
            'sort_order',
            'created_at',
            'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumn('knowledge_domains', 'plant_type_id'));

        $plantType = $this->plantType('schema-plant', 'Schema Plant');
        $domain = KnowledgeDomain::query()->create([
            'name' => 'Legacy Plant Domain',
            'slug' => 'legacy-plant-domain',
            'plant_type_id' => $plantType->id,
            'quiz_question_count' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $migration = require database_path('migrations/2026_08_12_000001_create_knowledge_domain_plant_type_table.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseHas('knowledge_domain_plant_type', [
            'knowledge_domain_id' => $domain->id,
            'plant_type_id' => $plantType->id,
            'is_primary' => true,
        ]);

        $migration->down();
        $this->assertFalse(Schema::hasTable('knowledge_domain_plant_type'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('knowledge_domain_plant_type'));
    }

    public function test_create_and_update_sync_selected_plant_types_to_pivot(): void
    {
        $admin = User::factory()->admin()->create();
        $first = $this->plantType('combined-cycle', 'Combined Cycle');
        $second = $this->plantType('solar-pv', 'Solar PV');

        $this->actingAs($admin)->post(route('admin.dashboard.library.knowledge-domains.store'), [
            'name' => 'Grid Storage',
            'slug' => '',
            'description' => 'Storage operations.',
            'plant_type_ids' => [$first->id, $second->id],
            'icon' => 'storage',
            'quiz_question_count' => 20,
            'is_active' => 1,
            'sort_order' => 3,
        ])->assertRedirect();

        $domain = KnowledgeDomain::query()->where('slug', 'grid-storage')->firstOrFail();
        $this->assertSame($first->id, $domain->plant_type_id);
        $this->assertSame([$first->id, $second->id], $domain->plantTypes()->pluck('plant_types.id')->all());

        $this->actingAs($admin)->put(route('admin.dashboard.library.knowledge-domains.update', $domain), [
            'name' => 'Grid Storage',
            'slug' => 'grid-storage',
            'description' => 'Storage operations.',
            'plant_type_ids' => [$second->id],
            'icon' => 'storage',
            'quiz_question_count' => 12,
            'is_active' => 0,
            'sort_order' => 4,
        ])->assertRedirect();

        $this->assertSame($second->id, $domain->refresh()->plant_type_id);
        $this->assertSame([$second->id], $domain->plantTypes()->pluck('plant_types.id')->all());
        $this->assertFalse($domain->is_active);
        $this->assertSame(12, $domain->quiz_question_count);
    }

    public function test_listing_status_tabs_filter_and_removed_controls_stay_hidden(): void
    {
        $admin = User::factory()->admin()->create();
        KnowledgeDomain::query()->create([
            'name' => 'Active Listing Domain',
            'slug' => 'active-listing-domain',
            'quiz_question_count' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        KnowledgeDomain::query()->create([
            'name' => 'Inactive Listing Domain',
            'slug' => 'inactive-listing-domain',
            'quiz_question_count' => 10,
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.knowledge-domains.index'))
            ->assertOk()
            ->assertSeeInOrder(['All', 'Active', 'Inactive'])
            ->assertDontSee('name="status"', false)
            ->assertDontSee('Import');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.knowledge-domains.index', ['is_active' => 1]))
            ->assertOk()
            ->assertSee('Active Listing Domain')
            ->assertDontSee('Inactive Listing Domain');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.knowledge-domains.index', ['is_active' => 0]))
            ->assertOk()
            ->assertSee('Inactive Listing Domain')
            ->assertDontSee('Active Listing Domain');
    }

    public function test_create_form_contains_required_layout_bindings_and_slug_script(): void
    {
        $admin = User::factory()->admin()->create();
        $this->plantType('hydrogen', 'Hydrogen');

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.knowledge-domains.create'))
            ->assertOk()
            ->assertSee('Create this knowledge domain first.')
            ->assertSee('Cancel')
            ->assertSee('Create Domain')
            ->assertSee('Visible in Library')
            ->assertSee('Questions per Quiz')
            ->assertSee('slugWasEdited')
            ->assertSee('name="plant_type_ids[]"', false)
            ->assertSee('checkbox-chip-group')
            ->assertDontSee('<select id="plant_type_ids"', false)
            ->assertDontSee('multiple', false);
    }

    public function test_edit_screen_exposes_multi_plant_type_selection_and_selected_names(): void
    {
        $admin = User::factory()->admin()->create();
        $first = $this->plantType('wind', 'Wind');
        $second = $this->plantType('battery-storage', 'Battery Storage');
        $domain = KnowledgeDomain::query()->create([
            'name' => 'Renewables Operations',
            'slug' => 'renewables-operations',
            'quiz_question_count' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $domain->plantTypes()->sync([$first->id, $second->id]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.library.knowledge-domains.edit', $domain))
            ->assertOk()
            ->assertSee('name="plant_type_ids[]"', false)
            ->assertSee('checkbox-chip-group')
            ->assertDontSee('<select id="plant_type_ids"', false)
            ->assertDontSee('multiple', false)
            ->assertSee('Wind')
            ->assertSee('Battery Storage')
            ->assertSee('value="1" checked', false)
            ->assertSee('value="2" checked', false);
    }

    private function plantType(string $slug, string $name): PlantType
    {
        return PlantType::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => "{$name} plant type.",
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
    }
}
