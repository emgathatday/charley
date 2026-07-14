<?php

namespace Database\Factories;

use App\Models\QuizQuestion;
use App\Models\QuizQuestionChoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestionChoice>
 */
class QuizQuestionChoiceFactory extends Factory
{
    protected $model = QuizQuestionChoice::class;

    public function definition(): array
    {
        return [
            'question_id' => QuizQuestion::query()->inRandomOrder()->value('id') ?? QuizQuestion::factory(),
            'choice_text' => fake()->sentence(),
            'is_correct' => fake()->boolean(25),
            'explanation' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (): array => [
            'is_correct' => true,
        ]);
    }
}
