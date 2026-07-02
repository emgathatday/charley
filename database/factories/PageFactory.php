<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Model>
 */
class PageFactory extends Factory
{
    protected $model = PageFactoryModel::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->bothify('####'),
            'content_blocks' => [
                [
                    'type' => 'paragraph',
                    'content' => fake()->paragraph(),
                ],
            ],
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'is_system_page' => false,
            'view_count' => fake()->numberBetween(0, 1000),
            'seo_meta' => [
                'title' => $title,
                'description' => fake()->sentence(),
            ],
            'user_id' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function systemPage(): static
    {
        return $this->state(fn (array $attributes): array => ['is_system_page' => true]);
    }
}

class PageFactoryModel extends Model
{
    protected $table = 'pages';

    protected $guarded = [];

    protected $casts = [
        'content_blocks' => 'array',
        'seo_meta' => 'array',
    ];
}
