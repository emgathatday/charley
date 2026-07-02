<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $tagModel = new class extends Model {
            protected $table = 'tags';

            protected $guarded = [];
        };

        foreach ($this->tags() as $tag) {
            $tagModel->newQuery()->firstOrCreate(
                ['slug' => $this->slug($tag['name'])],
                [
                    'name' => $tag['name'],
                    'category' => $tag['category'],
                    'usage_count' => 0,
                ],
            );
        }
    }

    private function tags(): array
    {
        return [
            ['name' => 'Corrosion Control', 'category' => 'technical'],
            ['name' => 'Pressure Safety', 'category' => 'technical'],
            ['name' => 'Predictive Maintenance', 'category' => 'technical'],
            ['name' => 'Process Plant', 'category' => 'plant_type'],
            ['name' => 'Utility System', 'category' => 'plant_type'],
            ['name' => 'Production Unit', 'category' => 'plant_type'],
            ['name' => 'Compressor', 'category' => 'equipment'],
            ['name' => 'Heat Exchanger', 'category' => 'equipment'],
            ['name' => 'Reactor', 'category' => 'equipment'],
            ['name' => 'Startup Procedure', 'category' => 'process'],
            ['name' => 'Shutdown Procedure', 'category' => 'process'],
            ['name' => 'Process Optimization', 'category' => 'process'],
            ['name' => 'Best Practice', 'category' => 'general'],
            ['name' => 'Troubleshooting', 'category' => 'general'],
            ['name' => 'Training', 'category' => 'general'],
        ];
    }

    private function slug(string $name): string
    {
        return Str::slug($name);
    }
}
