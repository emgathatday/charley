<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class ExpertiseLevelSeeder extends Seeder
{
    public function run(): void
    {
        $model = new ExpertiseLevelSeedModel();

        foreach ($this->levels() as $level) {
            $model->newQuery()->firstOrCreate(
                ['code' => $level['code']],
                $level,
            );
        }
    }

    private function levels(): array
    {
        return [
            [
                'name' => 'Industry Professional',
                'code' => 'industry_professional',
                'min_years_experience' => 0,
                'badge_icon' => 'briefcase',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Experienced Professional',
                'code' => 'experienced_professional',
                'min_years_experience' => 8,
                'badge_icon' => 'award',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Senior Industry Expert',
                'code' => 'senior_industry_expert',
                'min_years_experience' => 15,
                'badge_icon' => 'shield-check',
                'sort_order' => 30,
                'is_active' => true,
            ],
        ];
    }
}

class ExpertiseLevelSeedModel extends Model
{
    protected $table = 'expertise_levels';

    protected $guarded = [];
}
