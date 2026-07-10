<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Model>
 */
class LibraryItemHotspotFactory extends Factory
{
    protected $model = LibraryItemHotspotFactoryModel::class;

    public function definition(): array
    {
        return [
            'library_item_id' => DB::table('library_items')->inRandomOrder()->value('id'),
            'knowledge_domain_id' => DB::table('knowledge_domains')->inRandomOrder()->value('id') ?? KnowledgeDomainFactory::new(),
            'label' => fake()->optional()->words(2, true),
            'shape_type' => fake()->randomElement(['rect', 'polygon', 'circle']),
            'coordinates' => ['x' => 10, 'y' => 12, 'width' => 24, 'height' => 18],
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function polygon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'shape_type' => 'polygon',
            'coordinates' => [[12, 18], [42, 20], [38, 44], [16, 40]],
        ]);
    }
}

class LibraryItemHotspotFactoryModel extends Model
{
    protected $table = 'library_item_hotspots';

    protected $guarded = [];

    protected $casts = [
        'coordinates' => 'array',
    ];
}