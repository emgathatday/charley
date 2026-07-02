<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class ContentApprovalQueueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'approvable_type' => fake()->randomElement(['post', 'library_item', 'video']),
            'approvable_id' => fake()->numberBetween(1, 1000),
            'submitted_by' => User::factory(),
            'submitter_tier' => null,
            'content_title' => fake()->sentence(5),
            'content_type_label' => fake()->randomElement(['Article', 'Document', 'Video']),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'assigned_to' => null,
            'admin_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'submitted_at' => now()->subDays(fake()->numberBetween(0, 14)),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => 'approved', 'reviewed_at' => now()]);
    }

    public function newModel(array $attributes = []): Model
    {
        return new class($attributes) extends Model
        {
            protected $table = 'content_approval_queue';
            protected $guarded = [];

            protected function casts(): array
            {
                return [
                    'reviewed_at' => 'datetime',
                    'submitted_at' => 'datetime',
                ];
            }
        };
    }
}
