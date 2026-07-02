<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class HomepageFeedPriorityFactory extends Factory
{
    protected $model = HomepageFeedPriorityFactoryModel::class;

    public function definition(): array
    {
        return [
            'content_type' => fake()->unique()->randomElement([
                'partner_announcement',
                'network_post',
                'unanswered_question',
                'library_item',
                'handbook_article',
                'event',
                'job',
                'poll',
                'service',
            ]),
            'priority_weight' => fake()->numberBetween(0, 100),
            'is_highlighted' => fake()->boolean(20),
            'highlight_color' => fake()->optional()->hexColor(),
            'is_active' => true,
            'updated_by' => null,
        ];
    }

    public function highlighted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_highlighted' => true,
            'highlight_color' => fake()->hexColor(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}

class HomepageFeedPriorityFactoryModel extends Model
{
    protected $table = 'homepage_feed_priorities';

    protected $guarded = [];
}
