<?php

namespace Database\Factories;

use App\Models\MemberSubscription;
use App\Models\MemberSubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberSubscription>
 */
class MemberSubscriptionFactory extends Factory
{
    protected $model = MemberSubscription::class;

    public function definition(): array
    {
        $startsAt = now()->subDays(fake()->numberBetween(1, 30));

        return [
            'user_id' => User::factory(),
            'plan_id' => MemberSubscriptionPlan::factory(),
            'status' => fake()->randomElement(['active', 'expired', 'cancelled']),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMonth(),
            'payment_method' => 'bank_transfer',
        ];
    }
}
