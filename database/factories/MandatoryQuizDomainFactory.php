<?php

namespace Database\Factories;

use App\Models\KnowledgeDomain;
use App\Models\MandatoryQuizDomain;
use App\Models\PlantType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MandatoryQuizDomain>
 */
class MandatoryQuizDomainFactory extends Factory
{
    protected $model = MandatoryQuizDomain::class;

    public function definition(): array
    {
        return [
            'plant_type_id' => PlantType::query()->inRandomOrder()->value('id')
                ?? PlantType::query()->firstOrCreate(
                    ['slug' => 'library-factory-demo-plant'],
                    [
                        'name' => 'Library Factory Demo Plant',
                        'description' => 'Demo plant type for library factory records.',
                        'is_active' => true,
                        'sort_order' => 999,
                    ]
                )->id,
            'knowledge_domain_id' => KnowledgeDomain::query()->inRandomOrder()->value('id') ?? KnowledgeDomain::factory(),
            'is_active' => fake()->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'is_active' => true,
        ]);
    }
}
