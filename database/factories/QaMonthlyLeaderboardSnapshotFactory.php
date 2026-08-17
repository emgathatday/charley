<?php

namespace Database\Factories;

use App\Models\MonthlyLeaderboardSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QaMonthlyLeaderboardSnapshotFactory extends Factory
{
    protected $model = MonthlyLeaderboardSnapshot::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'year_month' => fake()->dateTimeBetween('-1 year')->format('Y-m'),
            'total_points_in_month' => fake()->numberBetween(100, 2500),
            'rank_position' => fake()->unique()->numberBetween(1, 100),
            'created_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }

    public function forMonth(string $yearMonth): static
    {
        return $this->state(fn (array $attributes): array => [
            'year_month' => $yearMonth,
        ]);
    }
}
