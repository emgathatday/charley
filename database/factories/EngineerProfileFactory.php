<?php

namespace Database\Factories;

use App\Models\EngineerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EngineerProfile>
 */
class EngineerProfileFactory extends Factory
{
    protected $model = EngineerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'photo_media_id' => null,
            'bio' => null,
            'current_company' => null,
            'position' => null,
            'plant_name' => null,
            'experience_years' => null,
            'education' => null,
            'expertise_tags' => null,
            'industry_specialization' => null,
            'searchable_keywords' => null,
            'references' => null,
            'phone' => null,
            'linkedin_url' => null,
            'job_availability' => $this->faker->randomElement(['open', 'not_looking', 'open_to_opportunities']),
            'reputation_points' => $this->faker->numberBetween(0, 5000),
            'reputation_breakdown' => null,
            'ai_usage_count' => $this->faker->numberBetween(0, 150),
            'is_discoverable' => true,
            'privacy_settings' => [
                'show_email' => $this->faker->randomElement(['connections_only', 'public', 'none']),
                'show_phone' => $this->faker->randomElement(['connections_only', 'none']),
                'show_activity_feed' => $this->faker->boolean(80),
            ],
            'notification_preferences' => [
                'connection_requests' => true,
                'directory_mentions' => $this->faker->boolean(70),
                'verification_reminders' => true,
            ],
            'verification_document_media_id' => null,
            'verification_renewed_at' => null,
            'renewal_reminder_sent_at' => null,
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

    public function openToWork(): static
    {
        return $this->state(fn (array $attributes): array => [
            'job_availability' => $this->faker->randomElement(['open', 'open_to_opportunities']),
        ]);
    }

    public function withProfessionalDetails(): static
    {
        return $this->state(fn (array $attributes): array => [
            'bio' => $this->faker->paragraph(),
            'current_company' => $this->faker->company(),
            'position' => $this->faker->jobTitle(),
            'plant_name' => $this->faker->company().' Plant',
            'experience_years' => $this->faker->numberBetween(1, 35),
            'education' => $this->faker->sentence(),
            'expertise_tags' => $this->faker->randomElements([
                'process safety',
                'maintenance',
                'operations',
                'commissioning',
                'reliability',
                'automation',
            ], $this->faker->numberBetween(2, 4)),
            'industry_specialization' => $this->faker->randomElements([
                'fertilizer',
                'chemicals',
                'renewables',
                'utilities',
                'power',
            ], $this->faker->numberBetween(1, 3)),
            'searchable_keywords' => $this->faker->randomElements([
                'turnaround',
                'root cause analysis',
                'process optimization',
                'operator training',
                'asset integrity',
            ], $this->faker->numberBetween(2, 4)),
            'references' => [
                [
                    'name' => $this->faker->name(),
                    'company' => $this->faker->company(),
                    'role' => $this->faker->jobTitle(),
                ],
            ],
            'phone' => $this->faker->phoneNumber(),
            'linkedin_url' => $this->faker->url(),
            'reputation_breakdown' => [
                'answers' => $this->faker->numberBetween(0, 1200),
                'contributions' => $this->faker->numberBetween(0, 1200),
                'endorsements' => $this->faker->numberBetween(0, 1200),
            ],
        ]);
    }

    public function verifiedRecently(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verification_renewed_at' => $this->faker->dateTimeBetween('-6 months'),
            'renewal_reminder_sent_at' => null,
        ]);
    }
}
