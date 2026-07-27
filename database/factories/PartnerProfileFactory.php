<?php

namespace Database\Factories;

use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerProfile>
 */
class PartnerProfileFactory extends Factory
{
    protected $model = PartnerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => fake()->unique()->company(),
            'logo_media_id' => null,
            'overview' => fake()->paragraph(),
            'company_type' => fake()->optional()->randomElement(['Licensor', 'Vendor', 'Catalyst supplier', 'Service provider']),
            'active_partner_subscription_id' => null,
            'plant_type_id' => null,
            'keywords' => fake()->randomElements(['licensor', 'vendor', 'catalyst', 'technology', 'services'], 3),
            'references' => [
                ['project' => fake()->company(), 'year' => fake()->numberBetween(2015, 2026)],
            ],
            'contact_email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'country' => fake()->country(),
            'website' => fake()->url(),
            'founded_year' => fake()->numberBetween(1950, 2020),
            'social_links' => ['linkedin' => fake()->url()],
            'layout_template' => fake()->randomElement(['layout_1', 'layout_2', 'layout_3']),
            'feed_highlight_enabled' => fake()->boolean(80),
            'subscription_status' => fake()->randomElement(['inactive', 'active']),
            'subscription_expires_at' => null,
            'approval_status' => fake()->randomElement(['pending', 'approved', 'rejected', 'suspended']),
            'verified_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'approval_status' => 'approved',
            'verified_at' => now(),
        ]);
    }

    public function activeSubscription(): static
    {
        return $this->state(fn (): array => [
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonth(),
        ]);
    }
}
