<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Model>
 */
class ExpertiseLevelFactory extends Factory
{
    protected $model = ExpertiseLevelFactoryModel::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Industry Professional',
            'Experienced Professional',
            'Senior Industry Expert',
        ]).' '.fake()->unique()->bothify('##');

        return [
            'name' => $name,
            'code' => Str::slug($name, '_'),
            'min_years_experience' => fake()->optional()->randomElement([0, 8, 15]),
            'badge_icon' => fake()->optional()->randomElement(['star', 'award', 'shield-check']),
            'sort_order' => fake()->numberBetween(1, 99),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}

class ExpertiseLevelFactoryModel extends Model
{
    protected $table = 'expertise_levels';

    protected $guarded = [];
}
