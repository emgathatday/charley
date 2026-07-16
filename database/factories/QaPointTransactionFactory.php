<?php

namespace Database\Factories;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QaPointTransactionFactory extends Factory
{
    protected $model = PointTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'points' => fake()->numberBetween(-50, 150),
            'source_type' => fake()->randomElement(['question', 'answer', 'manual_adjustment']),
            'source_id' => null,
            'reason' => null,
            'performed_by' => null,
            'created_at' => fake()->dateTimeBetween('-6 months'),
        ];
    }

    public function questionSource(int $questionId): static
    {
        return $this->state(fn (array $attributes): array => [
            'source_type' => 'question',
            'source_id' => $questionId,
        ]);
    }

    public function answerSource(int $answerId): static
    {
        return $this->state(fn (array $attributes): array => [
            'source_type' => 'answer',
            'source_id' => $answerId,
        ]);
    }

    public function manualAdjustment(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source_type' => 'manual_adjustment',
            'reason' => fake()->sentence(),
            'performed_by' => User::factory(),
        ]);
    }
}
