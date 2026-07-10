<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KnowledgeDomainSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('role', 'admin')->value('id')
            ?? DB::table('users')->value('id');

        $model = new KnowledgeDomainSeedModel();
        foreach ($this->domains($adminUserId) as $domain) {
            $model->newQuery()->firstOrCreate(
                ['slug' => $domain['slug']],
                $domain,
            );
        }
    }

    private function domains(?int $adminUserId): array
    {
        return [
            [
                'name' => 'Reformer',
                'slug' => 'reformer',
                'description' => 'Primary reformer reliability, operation and troubleshooting knowledge domain.',
                'status' => 'active',
                'created_by' => $adminUserId,
            ],
            [
                'name' => 'CO2 Removal',
                'slug' => 'co2-removal',
                'description' => 'Absorber, stripper, solvent quality and corrosion control knowledge domain.',
                'status' => 'active',
                'created_by' => $adminUserId,
            ],
            [
                'name' => 'Process Safety',
                'slug' => 'process-safety',
                'description' => 'Startup, shutdown, safeguards and operating discipline knowledge domain.',
                'status' => 'active',
                'created_by' => $adminUserId,
            ],
        ];
    }
}

class KnowledgeDomainSeedModel extends Model
{
    protected $table = 'knowledge_domains';

    protected $guarded = [];
}