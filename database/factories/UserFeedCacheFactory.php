<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class UserFeedCacheFactory extends Factory
{
    protected $model = UserFeedCacheFactoryModel::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'feedable_type' => 'App\\Models\\Page',
            'feedable_id' => fake()->numberBetween(1, 1000),
            'priority_score' => fake()->numberBetween(0, 100),
            'source_reason' => fake()->randomElement([
                'priority_rule',
                'followed_partner',
                'network_activity',
                'unanswered_question',
                'fresh_content',
                'admin_highlight',
            ]),
            'is_seen' => false,
            'created_at' => now(),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function seen(): static
    {
        return $this->state(fn (array $attributes): array => ['is_seen' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => ['expires_at' => now()->subHour()]);
    }
}

class UserFeedCacheFactoryModel extends Model
{
    public $timestamps = false;

    protected $table = 'user_feed_cache';

    protected $guarded = [];

    protected $casts = [
        'is_seen' => 'boolean',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
