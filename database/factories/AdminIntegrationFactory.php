<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class AdminIntegrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['outlook', 'gmail']),
            'access_token' => fake()->sha256(),
            'refresh_token' => null,
            'token_expires_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'config_metadata' => null,
        ];
    }

    public function gmail(): static
    {
        return $this->state(fn (): array => ['provider' => 'gmail']);
    }

    public function outlook(): static
    {
        return $this->state(fn (): array => ['provider' => 'outlook']);
    }

    public function newModel(array $attributes = []): Model
    {
        return new class($attributes) extends Model
        {
            protected $table = 'admin_integrations';
            protected $guarded = [];

            protected function casts(): array
            {
                return [
                    'token_expires_at' => 'datetime',
                    'config_metadata' => 'array',
                ];
            }
        };
    }
}
