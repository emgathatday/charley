<?php

namespace Database\Factories;

use App\Models\KnowledgeDomain;
use App\Models\MediaFile;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    public function definition(): array
    {
        $options = [
            fake()->sentence(4),
            fake()->sentence(4),
            fake()->sentence(4),
        ];

        $domainId = KnowledgeDomain::query()->inRandomOrder()->value('id') ?? KnowledgeDomain::factory();

        return [
            'quiz_id' => $this->quizId($domainId),
            'knowledge_domain_id' => $domainId,
            'question_text' => fake()->sentence(8),
            'question_type' => 'single_choice',
            'options' => $options,
            'correct_answer' => [$options[0]],
            'points' => fake()->numberBetween(1, 5),
            'explanation' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 100),
            'question_image_media_id' => MediaFile::query()->where('file_category', 'image')->inRandomOrder()->value('id'),
            'difficulty_level' => fake()->randomElement(['easy', 'medium', 'hard']),
            'status' => fake()->randomElement(['active', 'draft', 'archived']),
            'created_by' => User::query()->inRandomOrder()->value('id'),
            'updated_by' => User::query()->inRandomOrder()->value('id'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
        ]);
    }

    private function quizId($domainId): ?int
    {
        if (! Schema::hasTable('quizzes')) {
            return null;
        }

        $quiz = new class extends Model
        {
            protected $table = 'quizzes';

            protected $guarded = [];
        };

        return $quiz->newQuery()->firstOrCreate(
            ['slug' => fake()->unique()->slug(3)],
            [
                'knowledge_domain_id' => $domainId,
                'title' => fake()->sentence(3),
                'description' => null,
                'time_limit_minutes' => fake()->numberBetween(10, 45),
                'max_attempts_per_user' => fake()->numberBetween(1, 5),
                'status' => fake()->randomElement(['draft', 'published', 'archived']),
                'created_by' => User::query()->inRandomOrder()->value('id'),
            ]
        )->id;
    }
}
