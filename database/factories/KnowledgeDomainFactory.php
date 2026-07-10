<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Model>
 */
class KnowledgeDomainFactory extends Factory
{
    protected $model = KnowledgeDomainFactoryModel::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->bothify('####'),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['active', 'archived']),
            'created_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
        ]);
    }
}

class KnowledgeDomainFactoryModel extends Model
{
    protected $table = 'knowledge_domains';

    protected $guarded = [];
}