<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class SupportTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(6),
            'category' => fake()->randomElement(['subscription_support', 'technical_issue', 'content_approval', 'account_issue', 'other']),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'status' => fake()->randomElement(['open', 'pending', 'resolved', 'closed']),
            'description' => fake()->paragraph(),
            'assigned_to' => null,
            'resolved_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (): array => ['status' => 'open', 'resolved_at' => null]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => ['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function newModel(array $attributes = []): Model
    {
        return new class($attributes) extends Model
        {
            protected $table = 'support_tickets';
            protected $guarded = [];

            protected function casts(): array
            {
                return ['resolved_at' => 'datetime'];
            }
        };
    }
}
