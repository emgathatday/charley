<?php

namespace Database\Factories;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginToken>
 */
class LoginTokenFactory extends Factory
{
    protected $model = LoginToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => fake()->unique()->sha256(),
            'type' => fake()->randomElement(['magic_link', 'otp', 'email_verify', 'password_reset']),
            'is_used' => false,
            'expires_at' => fake()->dateTimeBetween('now', '+30 minutes'),
            'created_at' => now(),
        ];
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_used' => true,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-2 hours', '-1 minute'),
        ]);
    }
}
