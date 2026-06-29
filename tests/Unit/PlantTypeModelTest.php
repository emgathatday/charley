<?php

namespace Tests\Unit;

use App\Models\PlantType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantTypeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_plant_type_casts_active_flag_and_sort_order(): void
    {
        $plantType = PlantType::query()->create([
            'name' => 'Cast Test',
            'slug' => 'cast-test',
            'is_active' => 1,
            'sort_order' => '12',
        ]);

        $this->assertTrue($plantType->is_active);
        $this->assertSame(12, $plantType->sort_order);
    }

    public function test_active_scope_filters_inactive_records(): void
    {
        $active = $this->createPlantType(['name' => 'Active', 'slug' => 'active', 'is_active' => true]);
        $this->createPlantType(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => false]);

        $this->assertSame([$active->id], PlantType::query()->active()->pluck('id')->all());
    }

    public function test_sorted_scope_orders_by_sort_order_then_name(): void
    {
        $second = $this->createPlantType(['name' => 'Beta', 'slug' => 'beta', 'sort_order' => 10]);
        $first = $this->createPlantType(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 10]);
        $third = $this->createPlantType(['name' => 'Gamma', 'slug' => 'gamma', 'sort_order' => 20]);

        $this->assertSame(
            [$first->id, $second->id, $third->id],
            PlantType::query()->sorted()->pluck('id')->all()
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createPlantType(array $attributes): PlantType
    {
        return PlantType::query()->create(array_merge([
            'description' => null,
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }
}
