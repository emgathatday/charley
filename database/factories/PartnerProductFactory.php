<?php

namespace Database\Factories;

use App\Models\PartnerProduct;
use App\Models\PartnerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerProduct>
 */
class PartnerProductFactory extends Factory
{
    protected $model = PartnerProduct::class;

    public function definition(): array
    {
        return [
            'partner_id' => PartnerProfile::factory(),
            'name' => fake()->unique()->words(3, true),
            'category' => fake()->optional()->word(),
            'item_type' => fake()->randomElement(['product', 'service', 'technology']),
            'description' => fake()->paragraph(),
            'image_media_id' => null,
            'datasheet_media_id' => null,
            'keywords' => fake()->randomElements(['process', 'equipment', 'turnkey', 'optimization'], 2),
            'is_active' => fake()->boolean(90),
        ];
    }

    public function service(): static
    {
        return $this->state(fn (): array => ['item_type' => 'service']);
    }
}
