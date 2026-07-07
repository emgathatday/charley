<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Model>
 */
class LibraryCategoryFactory extends Factory
{
    protected $model = LibraryCategoryFactoryModel::class;

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
}

class LibraryCategoryFactoryModel extends Model
{
    protected $table = 'library_categories';

    protected $guarded = [];
}
