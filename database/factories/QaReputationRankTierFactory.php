<?php

namespace Database\Factories;

use App\Models\ReputationRankTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class QaReputationRankTierFactory extends Factory
{
    protected $model = ReputationRankTier::class;

    public function definition(): array
    {
        $starLevel = fake()->unique()->numberBetween(1, 5);

        return [
            'star_level' => $starLevel,
            'min_points' => fake()->numberBetween(0, 5000),
            'label' => fake()->words(2, true),
        ];
    }

    public function level(int $level): static
    {
        return $this->state(fn (array $attributes): array => [
            'star_level' => $level,
        ]);
    }
}
