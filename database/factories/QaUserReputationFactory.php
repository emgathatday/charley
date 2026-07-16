<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserReputation;
use Illuminate\Database\Eloquent\Factories\Factory;

class QaUserReputationFactory extends Factory
{
    protected $model = UserReputation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'total_points' => fake()->numberBetween(0, 5000),
            'current_star_rank' => fake()->numberBetween(1, 5),
            'updated_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }

    public function starRank(int $rank): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_star_rank' => $rank,
        ]);
    }
}
