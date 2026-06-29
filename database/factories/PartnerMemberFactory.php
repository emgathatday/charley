<?php

namespace Database\Factories;

use App\Models\PartnerMember;
use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerMember>
 */
class PartnerMemberFactory extends Factory
{
    protected $model = PartnerMember::class;

    public function definition(): array
    {
        return [
            'partner_id' => PartnerProfile::factory(),
            'user_id' => User::factory(),
            'member_role' => fake()->randomElement(['manager', 'staff', 'viewer']),
            'joined_at' => fake()->dateTimeBetween('-2 years'),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }

    public function manager(): static
    {
        return $this->state(fn (): array => ['member_role' => 'manager']);
    }
}
