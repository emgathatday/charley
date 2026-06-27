<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Connection>
 */
class ConnectionFactory extends Factory
{
    protected $model = Connection::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'accepted', 'declined', 'blocked']);

        return [
            'requester_id' => User::factory(),
            'receiver_id' => User::factory(),
            'status' => $status,
            'initiated_context' => $this->faker->randomElement([
                'engineer_to_engineer',
                'partner_to_engineer',
                'engineer_to_partner',
            ]),
            'declined_at' => $status === 'declined' ? $this->faker->dateTimeBetween('-3 months') : null,
            'accepted_at' => $status === 'accepted' ? $this->faker->dateTimeBetween('-3 months') : null,
            'blocked_at' => $status === 'blocked' ? $this->faker->dateTimeBetween('-3 months') : null,
            'blocked_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
            'declined_at' => null,
            'accepted_at' => null,
            'blocked_at' => null,
            'blocked_by' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'accepted',
            'declined_at' => null,
            'accepted_at' => $this->faker->dateTimeBetween('-3 months'),
            'blocked_at' => null,
            'blocked_by' => null,
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'declined',
            'declined_at' => $this->faker->dateTimeBetween('-3 months'),
            'accepted_at' => null,
            'blocked_at' => null,
            'blocked_by' => null,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'blocked',
            'declined_at' => null,
            'accepted_at' => null,
            'blocked_at' => $this->faker->dateTimeBetween('-3 months'),
            'blocked_by' => $attributes['requester_id'] ?? User::factory(),
        ]);
    }
}
