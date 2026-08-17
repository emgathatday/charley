<?php

namespace Database\Factories;

use App\Models\EngineerProfile;
use App\Models\SearchIndexEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchIndexEntry>
 */
class SearchIndexEntryFactory extends Factory
{
    protected $model = SearchIndexEntry::class;

    public function definition(): array
    {
        $tags = $this->faker->randomElements([
            'process safety',
            'maintenance',
            'operations',
            'commissioning',
            'reliability',
            'automation',
            'training',
        ], $this->faker->numberBetween(2, 4));

        return [
            'indexable_type' => EngineerProfile::class,
            'indexable_id' => EngineerProfile::factory(),
            'searchable_text' => implode(' ', [
                $this->faker->jobTitle(),
                $this->faker->company(),
                implode(' ', $tags),
                $this->faker->sentence(),
            ]),
            'structured_data' => [
                'profile_type' => EngineerProfile::class,
                'experience_years' => $this->faker->numberBetween(0, 35),
                'expertise_tags' => $tags,
                'searchable_keywords' => $this->faker->randomElements(['turnaround', 'operator training', 'asset integrity'], 2),
                'job_availability' => $this->faker->randomElement(['open', 'not_looking', 'open_to_opportunities']),
                'is_discoverable' => true,
                'plant_type_ids' => [],
                'plant_types' => [],
                'primary_plant_type_id' => null,
            ],
            'search_context' => 'expert_directory',
            'is_discoverable' => true,
            'last_indexed_at' => $this->faker->dateTimeBetween('-1 month'),
        ];
    }

    public function discoverable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_discoverable' => true,
            'structured_data' => array_merge($attributes['structured_data'] ?? [], ['is_discoverable' => true]),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_discoverable' => false,
            'structured_data' => array_merge($attributes['structured_data'] ?? [], ['is_discoverable' => false]),
        ]);
    }

    public function expertDirectory(): static
    {
        return $this->state(fn (array $attributes): array => [
            'search_context' => 'expert_directory',
            'indexable_type' => EngineerProfile::class,
        ]);
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes): array => [
            'search_context' => 'global',
        ]);
    }
}
