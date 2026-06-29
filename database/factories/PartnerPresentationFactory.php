<?php

namespace Database\Factories;

use App\Models\PartnerPresentation;
use App\Models\PartnerProfile;
use App\Models\PlantType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PartnerPresentation>
 */
class PartnerPresentationFactory extends Factory
{
    protected $model = PartnerPresentation::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'partner_id' => PartnerProfile::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraph(),
            'plant_type_id' => PlantType::query()->inRandomOrder()->value('id'),
            'equipment_category' => fake()->optional()->word(),
            'page_count' => fake()->optional()->numberBetween(5, 80),
            'download_allowed' => fake()->boolean(30),
            'view_count' => fake()->numberBetween(0, 1000),
            'status' => fake()->randomElement(['pending_approval', 'approved', 'rejected']),
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
            'is_ai_trainable' => fake()->boolean(20),
            'file_media_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => 'approved',
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }
}
