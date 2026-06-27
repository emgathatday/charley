<?php

namespace Database\Factories;

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
        $context = $this->faker->randomElement(['expert_directory', 'partner_directory', 'global']);
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
            'indexable_type' => \App\Models\EngineerProfile::class,
            'indexable_id' => EngineerProfileFactory::new(),
            'searchable_text' => implode(' ', [
                $this->faker->jobTitle(),
                $this->faker->company(),
                implode(' ', $tags),
                $this->faker->sentence(),
            ]),
            'structured_data' => [
                'tags' => $tags,
                'company' => $this->faker->company(),
                'position' => $this->faker->jobTitle(),
                'experience_years' => $this->faker->numberBetween(1, 35),
                'job_availability' => $this->faker->randomElement(['open', 'not_looking', 'open_to_opportunities']),
            ],
            'search_context' => $context,
            'is_discoverable' => true,
            'last_indexed_at' => $this->faker->dateTimeBetween('-1 month'),
        ];
    }

    public function discoverable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_discoverable' => true,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_discoverable' => false,
        ]);
    }

    public function expertDirectory(): static
    {
        return $this->state(fn (array $attributes): array => [
            'search_context' => 'expert_directory',
        ]);
    }

    public function partnerDirectory(): static
    {
        return $this->state(fn (array $attributes): array => [
            'search_context' => 'partner_directory',
        ]);
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes): array => [
            'search_context' => 'global',
        ]);
    }
}
