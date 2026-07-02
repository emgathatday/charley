<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class PlatformSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(3),
            'value' => fake()->word(),
            'group' => fake()->randomElement(['support', 'approval', 'notification', 'security']),
            'description' => null,
        ];
    }

    public function notification(): static
    {
        return $this->state(fn (): array => ['group' => 'notification']);
    }

    public function newModel(array $attributes = []): Model
    {
        return new class($attributes) extends Model
        {
            protected $table = 'platform_settings';
            protected $guarded = [];
        };
    }
}
