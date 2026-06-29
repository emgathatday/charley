<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class PlantTypeSeeder extends Seeder
{
    public function run(): void
    {
        $plantType = new class extends Model
        {
            protected $table = 'plant_types';

            protected $fillable = [
                'name',
                'slug',
                'description',
                'is_active',
                'sort_order',
            ];
        };

        $records = [
            [
                'name' => 'Ammonia',
                'slug' => 'ammonia-plant',
                'description' => 'Ammonia production plants and related industrial facilities.',
                'sort_order' => 10,
            ],
            [
                'name' => 'Methanol',
                'slug' => 'methanol-plant',
                'description' => 'Methanol production plants and downstream industrial facilities.',
                'sort_order' => 20,
            ],
            [
                'name' => 'Hydrogen',
                'slug' => 'hydrogen-plant',
                'description' => 'Hydrogen production, processing, and distribution facilities.',
                'sort_order' => 30,
            ],
            [
                'name' => 'SNG',
                'slug' => 'sng-plant',
                'description' => 'Synthetic natural gas plants and related conversion facilities.',
                'sort_order' => 40,
            ],
            [
                'name' => 'GTL',
                'slug' => 'gtl-plant',
                'description' => 'Gas-to-liquids plants and associated processing facilities.',
                'sort_order' => 50,
            ],
            [
                'name' => 'SAF',
                'slug' => 'saf-plant',
                'description' => 'Sustainable aviation fuel plants and related production facilities.',
                'sort_order' => 60,
            ],
            [
                'name' => 'Refineries',
                'slug' => 'refineries',
                'description' => 'Refineries and integrated downstream processing facilities.',
                'sort_order' => 70,
            ],
        ];

        foreach ($records as $record) {
            $plantType->newQuery()->firstOrCreate(
                ['slug' => $record['slug']],
                [
                    'name' => $record['name'],
                    'description' => $record['description'],
                    'is_active' => true,
                    'sort_order' => $record['sort_order'],
                ]
            );
        }
    }
}
