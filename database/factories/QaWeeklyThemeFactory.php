<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WeeklyTheme;
use Illuminate\Database\Eloquent\Factories\Factory;

class QaWeeklyThemeFactory extends Factory
{
    protected $model = WeeklyTheme::class;

    public function definition(): array
    {
        $weekStart = fake()->dateTimeBetween('-1 month', '+1 month')->modify('monday this week');

        return [
            'title' => fake()->sentence(4),
            'description' => null,
            'week_start_date' => $weekStart->format('Y-m-d'),
            'week_end_date' => (clone $weekStart)->modify('+6 days')->format('Y-m-d'),
            'created_by_admin_id' => User::factory(),
            'status' => fake()->randomElement(['active', 'archived']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'archived',
        ]);
    }
}
