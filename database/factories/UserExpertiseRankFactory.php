<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Model>
 */
class UserExpertiseRankFactory extends Factory
{
    protected $model = UserExpertiseRankFactoryModel::class;

    public function definition(): array
    {
        $userId = DB::table('users')->inRandomOrder()->value('id') ?? User::factory();

        return [
            'user_id' => $userId,
            'expertise_level_id' => DB::table('expertise_levels')->inRandomOrder()->value('id') ?? ExpertiseLevelFactory::new(),
            'plant_type_id' => DB::table('plant_types')->inRandomOrder()->value('id'),
            'handbook_category_id' => null,
            'source' => 'cv_review',
            'assigned_by' => DB::table('users')->where('role', 'admin')->value('id') ?? $userId,
            'quiz_attempt_id' => null,
            'notes' => fake()->optional()->sentence(),
            'is_current' => true,
            'assigned_at' => now(),
        ];
    }

    public function quizPass(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => 'quiz_pass',
            'assigned_by' => null,
            'quiz_attempt_id' => QuizAttemptFactory::new()->passed(),
        ]);
    }

    public function historical(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_current' => false,
        ]);
    }
}

class UserExpertiseRankFactoryModel extends Model
{
    protected $table = 'user_expertise_ranks';

    protected $guarded = [];

    protected $casts = [
        'is_current' => 'boolean',
        'assigned_at' => 'datetime',
    ];
}
