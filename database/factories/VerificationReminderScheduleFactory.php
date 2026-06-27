<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VerificationReminderSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationReminderSchedule>
 */
class VerificationReminderScheduleFactory extends Factory
{
    protected $model = VerificationReminderSchedule::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->professional(),
            'reminder_type' => fake()->randomElement(['30_days_before', '7_days_before', 'expiry_day', 'expired_notice']),
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 year'),
            'sent_at' => null,
            'status' => 'pending',
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'sent_at' => now(),
            'status' => 'sent',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
