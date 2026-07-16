<?php

namespace Database\Factories;

use App\Models\LeaderboardSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class QaLeaderboardSettingFactory extends Factory
{
    protected $model = LeaderboardSetting::class;

    public function definition(): array
    {
        return [
            'min_points_threshold' => fake()->numberBetween(0, 500),
            'top_n' => fake()->numberBetween(5, 50),
            'effective_from' => fake()->dateTimeBetween('-1 year', '+1 month')->format('Y-m-d'),
        ];
    }

    public function monthlyDefault(): static
    {
        return $this->state(fn (array $attributes): array => [
            'min_points_threshold' => 100,
            'top_n' => 10,
        ]);
    }
}
