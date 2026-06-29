<?php

namespace Database\Factories;

use App\Models\MemberSubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberSubscriptionPlan>
 */
class MemberSubscriptionPlanFactory extends Factory
{
    protected $model = MemberSubscriptionPlan::class;

    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'name' => $name,
            'display_name' => str($name)->headline()->toString(),
            'monthly_price' => fake()->randomFloat(2, 9, 99),
            'ai_monthly_limit' => fake()->randomElement([100, 500, -1]),
            'features' => fake()->randomElements(['ai_assistant', 'priority_support', 'library_access'], 2),
            'is_active' => true,
        ];
    }

    public function unlimitedAi(): static
    {
        return $this->state(fn (): array => ['ai_monthly_limit' => -1]);
    }
}
