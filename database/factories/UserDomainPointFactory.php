<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Model>
 */
class UserDomainPointFactory extends Factory
{
    protected $model = UserDomainPointFactoryModel::class;

    public function definition(): array
    {
        return [
            'user_id' => DB::table('users')->inRandomOrder()->value('id'),
            'knowledge_domain_id' => DB::table('knowledge_domains')->inRandomOrder()->value('id') ?? KnowledgeDomainFactory::new(),
            'total_points' => fake()->numberBetween(0, 120),
            'current_rank_tier_id' => DB::table('domain_rank_tiers')->inRandomOrder()->value('id'),
            'last_recalculated_at' => now(),
        ];
    }
}

class UserDomainPointFactoryModel extends Model
{
    protected $table = 'user_domain_points';

    protected $guarded = [];

    protected $casts = [
        'last_recalculated_at' => 'datetime',
    ];
}