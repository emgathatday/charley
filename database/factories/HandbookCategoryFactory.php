<?php

namespace Database\Factories;

use App\Models\HandbookCategory;
use App\Models\MediaFile;
use App\Models\PlantType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HandbookCategory>
 */
class HandbookCategoryFactory extends Factory
{
    protected $model = HandbookCategory::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->bothify('####'),
            'plant_type_id' => PlantType::query()->inRandomOrder()->value('id'),
            'parent_id' => null,
            'layout_image_media_id' => null,
            'map_coordinates' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(['draft', 'published']),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
        ]);
    }

    public function withLayoutImage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'layout_image_media_id' => MediaFile::query()->where('file_category', 'image')->inRandomOrder()->value('id')
                ?? MediaFile::query()->inRandomOrder()->value('id'),
        ]);
    }

    public function withHotspot(): static
    {
        return $this->state(fn (array $attributes): array => [
            'map_coordinates' => [
                'x' => fake()->numberBetween(5, 90),
                'y' => fake()->numberBetween(5, 90),
                'label' => fake()->words(2, true),
            ],
        ]);
    }
}
