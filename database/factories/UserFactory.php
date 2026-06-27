<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $verified = fake()->boolean(70);

        return [
            'username' => fake()->unique()->userName(),
            'first_name' => fake()->optional()->firstName(),
            'last_name' => fake()->optional()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(['admin', 'unverified_member', 'professional', 'partner']),
            'is_verified' => $verified,
            'verified_at' => $verified ? fake()->dateTimeBetween('-1 year') : null,
            'verification_expires_at' => $verified ? fake()->dateTimeBetween('now', '+1 year') : null,
            'status' => fake()->randomElement(['active', 'suspended', 'frozen']),
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days'),
            'login_attempts' => fake()->numberBetween(0, 5),
            'locked_until' => fake()->optional(0.1)->dateTimeBetween('now', '+1 hour'),
            'mfa_enabled' => fake()->boolean(25),
            'mfa_secret' => fake()->optional(0.25)->sha256(),
            'mfa_recovery_codes' => fake()->optional(0.25)->randomElements([
                fake()->regexify('[A-Z0-9]{10}'),
                fake()->regexify('[A-Z0-9]{10}'),
                fake()->regexify('[A-Z0-9]{10}'),
            ], fake()->numberBetween(1, 3)),
            'self_frozen_at' => null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'unverified_member',
            'is_verified' => false,
            'verified_at' => null,
            'verification_expires_at' => null,
        ]);
    }

    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'professional',
            'is_verified' => true,
            'verified_at' => now(),
            'verification_expires_at' => now()->addYear(),
            'status' => 'active',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'is_verified' => true,
            'verified_at' => now(),
            'verification_expires_at' => null,
            'status' => 'active',
        ]);
    }

    public function frozen(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'frozen',
            'self_frozen_at' => now(),
        ]);
    }
}
