<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Model>
 */
class DomainRankTierFactory extends Factory
{
    protected $model = DomainRankTierFactoryModel::class;

    public function definition(): array
    {
        $sortOrder = fake()->numberBetween(1, 5);

        return [
            'knowledge_domain_id' => DB::table('knowledge_domains')->inRandomOrder()->value('id') ?? KnowledgeDomainFactory::new(),
            'name' => fake()->unique()->words(2, true),
            'min_points' => $sortOrder * fake()->numberBetween(10, 30),
            'badge_icon' => fake()->optional()->randomElement(['award', 'shield-check', 'star', 'medal']),
            'sort_order' => $sortOrder,
        ];
    }

    public function entry(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Foundation',
            'min_points' => 0,
            'sort_order' => 1,
        ]);
    }
}

class DomainRankTierFactoryModel extends Model
{
    protected $table = 'domain_rank_tiers';

    protected $guarded = [];
}