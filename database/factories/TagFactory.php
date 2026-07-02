<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Model>
 */
class TagFactory extends Factory
{
    protected $model = TagFactoryModel::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(fake()->numberBetween(1, 3), true);

        return [
            'name' => Str::headline($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->bothify('####'),
            'category' => fake()->randomElement(['technical', 'plant_type', 'equipment', 'process', 'general']),
            'usage_count' => 0,
        ];
    }

    public function technical(): static
    {
        return $this->state(fn (array $attributes): array => ['category' => 'technical']);
    }

    public function plantType(): static
    {
        return $this->state(fn (array $attributes): array => ['category' => 'plant_type']);
    }

    public function equipment(): static
    {
        return $this->state(fn (array $attributes): array => ['category' => 'equipment']);
    }

    public function process(): static
    {
        return $this->state(fn (array $attributes): array => ['category' => 'process']);
    }

    public function general(): static
    {
        return $this->state(fn (array $attributes): array => ['category' => 'general']);
    }
}

class TagFactoryModel extends Model
{
    protected $table = 'tags';

    protected $guarded = [];
}
