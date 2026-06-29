<?php

namespace Tests\Feature;

use App\Models\PlantType;
use App\Models\User;
use Database\Seeders\PlantTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlantTypeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_plant_type_schema_contains_expected_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('plant_types', [
            'id',
            'name',
            'slug',
            'description',
            'is_active',
            'sort_order',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_plant_type_seeder_is_idempotent(): void
    {
        $seeder = new PlantTypeSeeder();

        $seeder->run();
        $seeder->run();

        $this->assertSame(7, PlantType::query()->count());
        $this->assertDatabaseHas('plant_types', [
            'name' => 'Ammonia',
            'slug' => 'ammonia-plant',
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    public function test_api_list_requires_authentication(): void
    {
        $this->getJson('/api/v1/plant-types')->assertUnauthorized();
    }

    public function test_api_list_returns_active_sorted_resource_collection(): void
    {
        $user = User::factory()->professional()->create();
        $second = $this->createPlantType(['name' => 'Beta', 'slug' => 'beta', 'sort_order' => 20]);
        $first = $this->createPlantType(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 10]);
        $this->createPlantType(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => false, 'sort_order' => 1]);

        $this->actingAs($user)
            ->getJson('/api/v1/plant-types')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'is_active',
                        'sort_order',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id);
    }

    public function test_admin_can_include_inactive_plant_types(): void
    {
        $admin = User::factory()->admin()->create();
        $inactive = $this->createPlantType(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => false]);

        $this->actingAs($admin)
            ->getJson('/api/v1/plant-types?include_inactive=1')
            ->assertOk()
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false]);
    }

    public function test_api_show_returns_plant_type_resource(): void
    {
        $user = User::factory()->professional()->create();
        $plantType = $this->createPlantType();

        $this->actingAs($user)
            ->getJson("/api/v1/plant-types/{$plantType->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $plantType->id)
            ->assertJsonPath('data.slug', 'plant-type');
    }

    public function test_api_missing_plant_type_returns_not_found(): void
    {
        $user = User::factory()->professional()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/plant-types/999999')
            ->assertNotFound();
    }

    public function test_admin_can_create_update_and_delete_plant_type(): void
    {
        $admin = User::factory()->admin()->create();

        $createResponse = $this->actingAs($admin)
            ->postJson('/api/v1/plant-types', [
                'name' => 'Created',
                'slug' => 'created',
                'description' => null,
                'is_active' => true,
                'sort_order' => 5,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Created')
            ->assertJsonPath('data.description', null);

        $plantTypeId = $createResponse->json('data.id');

        $this->actingAs($admin)
            ->putJson("/api/v1/plant-types/{$plantTypeId}", [
                'name' => 'Updated',
                'slug' => 'updated',
                'description' => 'Updated description.',
                'is_active' => false,
                'sort_order' => 15,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/plant-types/{$plantTypeId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('plant_types', ['id' => $plantTypeId]);
    }

    public function test_non_admin_cannot_write_or_delete_plant_types(): void
    {
        $user = User::factory()->professional()->create();
        $plantType = $this->createPlantType();

        $this->actingAs($user)
            ->postJson('/api/v1/plant-types', [
                'name' => 'Denied',
                'slug' => 'denied',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->putJson("/api/v1/plant-types/{$plantType->id}", [
                'name' => 'Denied',
                'slug' => 'denied',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson("/api/v1/plant-types/{$plantType->id}")
            ->assertForbidden();
    }

    public function test_api_validation_rejects_invalid_plant_type_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = $this->createPlantType(['name' => 'Existing', 'slug' => 'existing']);

        $this->actingAs($admin)
            ->postJson('/api/v1/plant-types', [
                'name' => $existing->name,
                'slug' => $existing->slug,
                'description' => ['not-string'],
                'is_active' => 'not-boolean',
                'sort_order' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug', 'description', 'is_active', 'sort_order']);

        $this->actingAs($admin)
            ->postJson('/api/v1/plant-types', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_api_update_allows_current_unique_values(): void
    {
        $admin = User::factory()->admin()->create();
        $plantType = $this->createPlantType();

        $this->actingAs($admin)
            ->putJson("/api/v1/plant-types/{$plantType->id}", [
                'name' => $plantType->name,
                'slug' => $plantType->slug,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $plantType->id);
    }

    public function test_admin_web_routes_require_admin_access(): void
    {
        $this->get('/dashboard/plant-types')->assertRedirect('/login');

        $user = User::factory()->professional()->create();

        $this->actingAs($user)
            ->get('/dashboard/plant-types')
            ->assertForbidden();
    }

    public function test_admin_web_can_manage_plant_types(): void
    {
        $admin = User::factory()->admin()->create();
        $plantType = $this->createPlantType();

        $this->actingAs($admin)
            ->get('/dashboard/plant-types')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/dashboard/plant-types/create')
            ->assertOk();

        $this->actingAs($admin)
            ->post('/dashboard/plant-types', [
                'name' => 'Admin Created',
                'slug' => 'admin-created',
                'description' => null,
                'is_active' => '1',
                'sort_order' => '25',
            ])
            ->assertRedirect('/dashboard/plant-types');

        $this->assertDatabaseHas('plant_types', ['slug' => 'admin-created']);

        $this->actingAs($admin)
            ->get("/dashboard/plant-types/{$plantType->id}/edit")
            ->assertOk();

        $this->actingAs($admin)
            ->put("/dashboard/plant-types/{$plantType->id}", [
                'name' => 'Admin Updated',
                'slug' => 'admin-updated',
                'description' => 'Updated from admin.',
                'is_active' => '0',
                'sort_order' => '30',
            ])
            ->assertRedirect("/dashboard/plant-types/{$plantType->id}/edit");

        $this->assertDatabaseHas('plant_types', [
            'id' => $plantType->id,
            'slug' => 'admin-updated',
            'is_active' => false,
            'sort_order' => 30,
        ]);
    }

    public function test_admin_web_validation_rejects_invalid_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = $this->createPlantType(['name' => 'Existing', 'slug' => 'existing']);

        $this->actingAs($admin)
            ->from('/dashboard/plant-types/create')
            ->post('/dashboard/plant-types', [
                'name' => $existing->name,
                'slug' => $existing->slug,
                'is_active' => 'bad',
                'sort_order' => '-1',
            ])
            ->assertRedirect('/dashboard/plant-types/create')
            ->assertSessionHasErrors(['name', 'slug', 'is_active', 'sort_order']);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createPlantType(array $attributes = []): PlantType
    {
        return PlantType::query()->create(array_merge([
            'name' => 'Plant Type',
            'slug' => 'plant-type',
            'description' => 'Plant type description.',
            'is_active' => true,
            'sort_order' => 10,
        ], $attributes));
    }
}
