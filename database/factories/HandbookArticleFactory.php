<?php

namespace Database\Factories;

use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HandbookArticle>
 */
class HandbookArticleFactory extends Factory
{
    protected $model = HandbookArticle::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'category_id' => HandbookCategory::factory(),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->bothify('####'),
            'summary' => fake()->paragraph(),
            'content' => fake()->paragraphs(5, true),
            'optimization_guidance' => null,
            'failure_modes' => null,
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'is_ai_trainable' => fake()->boolean(80),
            'ai_shortcut_config' => null,
            'view_count' => fake()->numberBetween(0, 2000),
            'process_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
        ]);
    }

    public function aiTrainable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_ai_trainable' => true,
            'ai_shortcut_config' => [
                'enabled' => true,
                'prompt_key' => fake()->slug(3),
            ],
        ]);
    }

    public function withProcessData(): static
    {
        return $this->state(fn (array $attributes): array => [
            'optimization_guidance' => fake()->paragraph(),
            'failure_modes' => [
                [
                    'mode' => fake()->words(3, true),
                    'mitigation' => fake()->sentence(),
                ],
            ],
            'process_description' => fake()->paragraphs(2, true),
        ]);
    }
}
