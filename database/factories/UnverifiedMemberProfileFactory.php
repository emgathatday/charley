<?php

namespace Database\Factories;

use App\Models\UnverifiedMemberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnverifiedMemberProfile>
 */
class UnverifiedMemberProfileFactory extends Factory
{
    protected $model = UnverifiedMemberProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'photo_media_id' => null,
            'bio' => null,
            'current_institution' => null,
            'field_of_study' => null,
            'experience_years' => null,
            'education' => null,
            'references' => null,
            'expertise_tags' => null,
            'searchable_keywords' => null,
            'is_discoverable' => true,
            'privacy_settings' => [
                'show_email' => $this->faker->randomElement(['connections_only', 'public', 'none']),
                'show_phone' => $this->faker->randomElement(['connections_only', 'none']),
                'show_activity_feed' => $this->faker->boolean(70),
            ],
            'notification_preferences' => [
                'connection_requests' => true,
                'directory_mentions' => $this->faker->boolean(60),
                'verification_reminders' => true,
            ],
            'linkedin_url' => null,
            'job_availability' => $this->faker->randomElement(['open', 'not_looking', 'open_to_opportunities']),
            'verification_intent' => false,
        ];
    }

    public function discoverable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_discoverable' => true,
        ]);
    }

    public function hiddenFromDirectory(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_discoverable' => false,
        ]);
    }

    public function wantsVerification(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verification_intent' => true,
        ]);
    }

    public function withProfileDetails(): static
    {
        return $this->state(fn (array $attributes): array => [
            'bio' => $this->faker->paragraph(),
            'current_institution' => $this->faker->company(),
            'field_of_study' => $this->faker->randomElement([
                'Mechanical Engineering',
                'Chemical Engineering',
                'Electrical Engineering',
                'Industrial Engineering',
                'Process Engineering',
            ]),
            'experience_years' => $this->faker->numberBetween(0, 8),
            'education' => $this->faker->sentence(),
            'references' => [
                [
                    'name' => $this->faker->name(),
                    'context' => $this->faker->sentence(),
                ],
            ],
            'expertise_tags' => $this->faker->randomElements([
                'internship',
                'operations',
                'maintenance',
                'process design',
                'research',
                'quality control',
            ], $this->faker->numberBetween(2, 4)),
            'searchable_keywords' => $this->faker->randomElements([
                'graduate engineer',
                'plant operations',
                'training',
                'entry level',
                'process safety',
            ], $this->faker->numberBetween(2, 4)),
            'linkedin_url' => $this->faker->url(),
        ]);
    }
}
