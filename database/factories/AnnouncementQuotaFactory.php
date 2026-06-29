<?php

namespace Database\Factories;

use App\Models\AnnouncementQuota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnouncementQuota>
 */
class AnnouncementQuotaFactory extends Factory
{
    protected $model = AnnouncementQuota::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'period' => now()->format('Y-m'),
            'used_count' => fake()->numberBetween(0, 3),
            'quota_limit' => fake()->numberBetween(4, 20),
        ];
    }
}
