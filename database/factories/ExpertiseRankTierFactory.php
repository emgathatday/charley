<?php

namespace Database\Factories;

use App\Models\ExpertiseRankTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExpertiseRankTier>
 */
class ExpertiseRankTierFactory extends Factory
{
    protected $model = ExpertiseRankTier::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->bothify('####'),
            'min_years_experience' => fake()->optional()->numberBetween(0, 15),
            'default_cap_percentage' => fake()->randomFloat(2, 30, 100),
            'rank_order' => fake()->unique()->numberBetween(1, 1000),
            'required_quiz_count' => fake()->numberBetween(1, 20),
            'required_mandatory_quiz_count' => fake()->numberBetween(1, 5),
            'status' => fake()->randomElement(['active', 'draft', 'deleted']),
            'is_active' => fake()->boolean(90),
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
