<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Model>
 */
class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttemptFactoryModel::class;

    public function definition(): array
    {
        $maxScore = fake()->numberBetween(5, 30);

        return [
            'quiz_id' => DB::table('quizzes')->inRandomOrder()->value('id') ?? QuizFactory::new(),
            'user_id' => DB::table('users')->inRandomOrder()->value('id'),
            'attempt_number' => fake()->numberBetween(1, 4),
            'answers_submitted' => ['1' => 0, '2' => 1],
            'score' => fake()->numberBetween(0, $maxScore),
            'max_possible_score' => $maxScore,
            'started_at' => now()->subMinutes(fake()->numberBetween(20, 90)),
            'completed_at' => now()->subMinutes(fake()->numberBetween(1, 19)),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'completed_at' => now(),
        ]);
    }
}

class QuizAttemptFactoryModel extends Model
{
    protected $table = 'quiz_attempts';

    protected $guarded = [];

    protected $casts = [
        'answers_submitted' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}