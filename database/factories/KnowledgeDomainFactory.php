<?php

namespace Database\Factories;

use App\Models\KnowledgeDomain;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeDomain>
 */
class KnowledgeDomainFactory extends Factory
{
    protected $model = KnowledgeDomain::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->bothify('####'),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['active', 'archived']),
            'created_by' => User::query()->inRandomOrder()->value('id'),
            'plant_type_id' => PlantType::query()->inRandomOrder()->value('id'),
            'icon' => null,
            'total_question_count' => fake()->numberBetween(0, 50),
            'is_active' => fake()->boolean(90),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}
