<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Model>
 */
class UserQuizBestScoreFactory extends Factory
{
    protected $model = UserQuizBestScoreFactoryModel::class;

    public function definition(): array
    {
        return [
            'user_id' => DB::table('users')->inRandomOrder()->value('id'),
            'quiz_id' => DB::table('quizzes')->inRandomOrder()->value('id') ?? QuizFactory::new(),
            'best_score' => fake()->numberBetween(0, 30),
            'best_quiz_attempt_id' => DB::table('quiz_attempts')->inRandomOrder()->value('id'),
            'achieved_at' => now(),
        ];
    }
}

class UserQuizBestScoreFactoryModel extends Model
{
    protected $table = 'user_quiz_best_scores';

    protected $guarded = [];

    protected $casts = [
        'achieved_at' => 'datetime',
    ];
}