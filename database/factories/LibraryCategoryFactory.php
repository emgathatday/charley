<?php

namespace Database\Factories;

use App\Models\LibraryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LibraryCategory>
 */
class LibraryCategoryFactory extends Factory
{
    protected $model = LibraryCategory::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->bothify('####'),
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function childOf(LibraryCategory $category): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $category->id,
        ]);
    }
}
