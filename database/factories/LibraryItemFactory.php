<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Model>
 */
class LibraryItemFactory extends Factory
{
    protected $model = LibraryItemFactoryModel::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);
        $year = fake()->numberBetween(2018, (int) now()->format('Y'));

        return [
            'category_id' => null,
            'user_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->bothify('####'),
            'summary' => fake()->paragraph(),
            'content' => fake()->paragraphs(4, true),
            'plant_type_id' => null,
            'author' => fake()->name(),
            'source' => fake()->company(),
            'published_year' => $year,
            'access_level' => fake()->randomElement(['public', 'member', 'professional_only', 'partner_only', 'admin_only']),
            'download_allowed' => fake()->boolean(45),
            'copy_paste_disabled' => fake()->boolean(35),
            'download_count' => fake()->numberBetween(0, 250),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'is_ai_trainable' => fake()->boolean(80),
            'content_type' => fake()->randomElement(['article', 'video', 'document', 'presentation', 'case_study', 'safety_bulletin']),
            'item_type' => fake()->randomElement(['handbook', 'article', 'presentation', 'video', 'case_study', 'safety_bulletin', 'whitepaper']),
            'view_count' => fake()->numberBetween(0, 2000),
            'approved_by' => null,
            'approved_at' => null,
            'year' => $year,
            'file_media_id' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'approved_at' => now(),
        ]);
    }

    public function document(): static
    {
        return $this->state(fn (array $attributes): array => [
            'content_type' => 'document',
            'item_type' => 'whitepaper',
        ]);
    }
}

class LibraryItemFactoryModel extends Model
{
    protected $table = 'library_items';

    protected $guarded = [];
}
