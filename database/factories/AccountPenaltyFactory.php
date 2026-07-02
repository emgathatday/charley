<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class AccountPenaltyFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'user_id' => User::factory(),
            'action_type' => fake()->randomElement(['warning', 'temporary_suspension', 'account_freeze', 'unfreeze', 'ban', 'self_freeze', 'self_unfreeze']),
            'reason' => fake()->sentence(),
            'evidence_ref' => null,
            'duration_days' => null,
            'starts_at' => $startsAt,
            'ends_at' => null,
            'admin_id' => null,
        ];
    }

    public function temporarySuspension(): static
    {
        return $this->state(fn (): array => [
            'action_type' => 'temporary_suspension',
            'duration_days' => 7,
            'ends_at' => now()->addDays(7),
        ]);
    }

    public function newModel(array $attributes = []): Model
    {
        return new class($attributes) extends Model
        {
            protected $table = 'account_penalties';
            protected $guarded = [];

            protected function casts(): array
            {
                return [
                    'evidence_ref' => 'array',
                    'starts_at' => 'datetime',
                    'ends_at' => 'datetime',
                ];
            }
        };
    }
}
