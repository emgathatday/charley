<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<Model>
 */
class QuizFactory extends Factory
{
    protected $model = QuizFactoryModel::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'knowledge_domain_id' => DB::table('knowledge_domains')->inRandomOrder()->value('id') ?? KnowledgeDomainFactory::new(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->bothify('####'),
            'description' => fake()->optional()->paragraph(),
            'time_limit_minutes' => fake()->optional()->numberBetween(15, 90),
            'max_attempts_per_user' => fake()->optional()->numberBetween(1, 5),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'created_by' => DB::table('users')->where('role', 'admin')->value('id') ?? DB::table('users')->value('id'),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
        ]);
    }
}

class QuizFactoryModel extends Model
{
    protected $table = 'quizzes';

    protected $guarded = [];
}