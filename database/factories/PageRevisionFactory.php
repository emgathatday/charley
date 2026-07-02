<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class PageRevisionFactory extends Factory
{
    protected $model = PageRevisionFactoryModel::class;

    public function definition(): array
    {
        return [
            'page_id' => null,
            'content_blocks' => [
                [
                    'type' => 'paragraph',
                    'content' => fake()->paragraph(),
                ],
            ],
            'changed_by' => null,
            'change_summary' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

class PageRevisionFactoryModel extends Model
{
    public $timestamps = false;

    protected $table = 'page_revisions';

    protected $guarded = [];

    protected $casts = [
        'content_blocks' => 'array',
        'created_at' => 'datetime',
    ];
}
