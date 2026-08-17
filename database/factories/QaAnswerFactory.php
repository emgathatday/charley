<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QaAnswerFactory extends Factory
{
    protected $model = Answer::class;

    public function definition(): array
    {
        return [
            'question_id' => QaQuestionFactory::new(),
            'user_id' => User::factory(),
            'is_anonymous' => fake()->boolean(),
            'body' => fake()->paragraphs(2, true),
            'is_admin_featured' => false,
            'confidence_level' => null,
            'admin_rank_order' => null,
            'attachment_media_ids' => null,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_admin_featured' => true,
            'confidence_level' => fake()->randomElement(['medium', 'high']),
            'admin_rank_order' => fake()->numberBetween(1, 10),
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_anonymous' => true,
        ]);
    }

    public function withConfidence(): static
    {
        return $this->state(fn (array $attributes): array => [
            'confidence_level' => fake()->randomElement(['low', 'medium', 'high']),
        ]);
    }
}
