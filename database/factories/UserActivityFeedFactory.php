<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserActivityFeed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserActivityFeed>
 */
class UserActivityFeedFactory extends Factory
{
    protected $model = UserActivityFeed::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_type' => fake()->randomElement(['asked_question', 'answered_question', 'answer_accepted', 'contribution_approved', 'poll_voted', 'event_registered', 'library_uploaded', 'connection_made']),
            'subject_type' => null,
            'subject_id' => null,
            'is_public' => true,
            'created_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
