<?php

namespace Database\Factories;

use App\Models\PartnerSubscription;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    public function definition(): array
    {
        return [
            'partner_subscription_id' => PartnerSubscription::factory(),
            'amount' => fake()->randomFloat(2, 99, 999),
            'payment_method' => 'bank_transfer',
            'payment_proof_media_id' => null,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'transaction_code' => fake()->optional()->bothify('SUB-####-????'),
            'approved_by' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => 'approved']);
    }
}
