<?php

namespace Database\Factories;

use App\Models\PartnerSubscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerSubscription>
 */
class PartnerSubscriptionFactory extends Factory
{
    protected $model = PartnerSubscription::class;

    public function definition(): array
    {
        $startsAt = now()->subDays(fake()->numberBetween(1, 30));

        return [
            'user_id' => User::factory(),
            'tier_id' => SubscriptionTier::factory(),
            'status' => fake()->randomElement(['pending_approval', 'active', 'expired', 'cancelled']),
            'approved_by' => null,
            'approved_at' => null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMonth(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'approved_at' => now(),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }
}
