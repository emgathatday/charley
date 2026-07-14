<?php

namespace Database\Factories;

use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\MediaFile;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LibraryItem>
 */
class LibraryItemFactory extends Factory
{
    protected $model = LibraryItem::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'category_id' => LibraryCategory::query()->inRandomOrder()->value('id') ?? LibraryCategory::factory(),
            'user_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->bothify('####'),
            'summary' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'plant_type_id' => PlantType::query()->inRandomOrder()->value('id'),
            'author' => fake()->name(),
            'source' => fake()->company(),
            'published_year' => fake()->year(),
            'access_level' => fake()->randomElement(['public', 'professional_only', 'partner_only', 'gold', 'diamond', 'platinum']),
            'download_allowed' => fake()->boolean(),
            'copy_paste_disabled' => fake()->boolean(),
            'download_count' => fake()->numberBetween(0, 250),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'is_ai_trainable' => fake()->boolean(80),
            'content_type' => fake()->randomElement(['article', 'video', 'document', 'presentation', 'case_study', 'safety_bulletin']),
            'item_type' => null,
            'view_count' => fake()->numberBetween(0, 1000),
            'approved_by' => null,
            'approved_at' => null,
            'year' => null,
            'file_media_id' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => 'published',
            'approved_by' => User::query()->where('role', 'admin')->inRandomOrder()->value('id')
                ?? User::query()->inRandomOrder()->value('id'),
            'approved_at' => now(),
        ]);
    }

    public function withFile(): static
    {
        return $this->state(fn (): array => [
            'file_media_id' => MediaFile::query()->inRandomOrder()->value('id'),
        ]);
    }
}
