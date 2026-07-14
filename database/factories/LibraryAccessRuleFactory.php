<?php

namespace Database\Factories;

use App\Models\LibraryAccessRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryAccessRule>
 */
class LibraryAccessRuleFactory extends Factory
{
    protected $model = LibraryAccessRule::class;

    public function definition(): array
    {
        return [
            'partner_tier' => fake()->unique()->randomElement(['gold', 'diamond', 'platinum']),
            'can_view' => true,
            'can_download' => fake()->boolean(),
            'can_copy_paste' => fake()->boolean(),
            'requires_watermark' => fake()->boolean(),
            'max_downloads_per_month' => fake()->optional()->numberBetween(1, 100),
            'notes' => null,
            'updated_by' => User::query()->inRandomOrder()->value('id'),
        ];
    }
}
