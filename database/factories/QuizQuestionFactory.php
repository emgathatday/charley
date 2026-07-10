<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Model>
 */
class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestionFactoryModel::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['single_choice', 'multiple_choice', 'true_false']);
        $options = $type === 'true_false' ? ['True', 'False'] : fake()->words(4);
        $correctAnswer = $type === 'multiple_choice' ? [0, 2] : 0;

        return [
            'quiz_id' => DB::table('quizzes')->inRandomOrder()->value('id') ?? QuizFactory::new(),
            'question_text' => fake()->sentence().'?',
            'question_type' => $type,
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'points' => fake()->numberBetween(1, 5),
            'explanation' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function singleChoice(): static
    {
        return $this->state(fn (array $attributes): array => [
            'question_type' => 'single_choice',
            'correct_answer' => 0,
        ]);
    }
}

class QuizQuestionFactoryModel extends Model
{
    protected $table = 'quiz_questions';

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];
}