<?php

namespace Database\Factories;

use App\Models\SubscriptionTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionTier>
 */
class SubscriptionTierFactory extends Factory
{
    protected $model = SubscriptionTier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['gold', 'diamond', 'platinum']),
            'monthly_price' => fake()->randomFloat(2, 99, 999),
            'ai_monthly_limit' => fake()->randomElement([100, 500, -1]),
            'announcement_frequency' => fake()->randomElement(['weekly', 'monthly']),
            'announcement_limit' => fake()->numberBetween(1, 20),
            'can_host_webinar' => fake()->boolean(),
            'can_initiate_message' => fake()->boolean(),
            'can_create_poll' => fake()->boolean(),
            'can_publish_events' => fake()->boolean(),
            'is_active' => true,
        ];
    }

    public function unlimitedAi(): static
    {
        return $this->state(fn (): array => ['ai_monthly_limit' => -1]);
    }
}
