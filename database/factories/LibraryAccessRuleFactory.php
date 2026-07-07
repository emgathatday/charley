<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class LibraryAccessRuleFactory extends Factory
{
    protected $model = LibraryAccessRuleFactoryModel::class;

    public function definition(): array
    {
        return [
            'partner_tier' => fake()->unique()->randomElement(['gold', 'diamond', 'platinum']),
            'can_view' => true,
            'can_download' => fake()->boolean(60),
            'can_copy_paste' => fake()->boolean(40),
            'requires_watermark' => fake()->boolean(75),
            'max_downloads_per_month' => fake()->optional()->numberBetween(5, 100),
            'notes' => fake()->optional()->sentence(),
            'updated_by' => null,
        ];
    }
}

class LibraryAccessRuleFactoryModel extends Model
{
    protected $table = 'library_access_rules';

    protected $guarded = [];
}
