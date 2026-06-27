<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationRequest>
 */
class VerificationRequestFactory extends Factory
{
    protected $model = VerificationRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'submission_type' => fake()->randomElement(['initial', 'renewal', 'resubmission']),
            'verification_method' => fake()->randomElement(['work_email', 'linkedin', 'company_letter', 'university_letter', 'justification_letter']),
            'document_media_ids' => fake()->optional()->randomElements(range(1, 50), fake()->numberBetween(1, 3)),
            'notes' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected', 'more_info_required']),
            'admin_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => User::factory()->admin(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'admin_notes' => fake()->sentence(),
            'reviewed_by' => User::factory()->admin(),
            'reviewed_at' => now(),
        ]);
    }
}
