<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QaQuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'posted_by_admin_id' => null,
            'on_behalf_of_partner_id' => null,
            'weekly_theme_id' => null,
            'plant_type_id' => null,
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'is_anonymous' => fake()->boolean(),
            'status' => fake()->randomElement(['pending', 'published', 'hidden', 'flagged']),
            'attachment_media_ids' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_anonymous' => true,
        ]);
    }

    public function withAttachments(array $mediaIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'attachment_media_ids' => $mediaIds,
        ]);
    }
}
