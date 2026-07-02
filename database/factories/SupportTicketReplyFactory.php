<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class SupportTicketReplyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => SupportTicketFactory::new(),
            'sender_id' => User::factory(),
            'content' => fake()->paragraph(),
            'is_internal_note' => false,
        ];
    }

    public function internalNote(): static
    {
        return $this->state(fn (): array => ['is_internal_note' => true]);
    }

    public function newModel(array $attributes = []): Model
    {
        return new class($attributes) extends Model
        {
            protected $table = 'support_ticket_replies';
            protected $guarded = [];
        };
    }
}
