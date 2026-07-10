<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DomainRankTierSeeder extends Seeder
{
    public function run(): void
    {
        $tierModel = new DomainRankTierSeedModel();

        foreach ($this->tiers() as $domainSlug => $tiers) {
            $domainId = DB::table('knowledge_domains')->where('slug', $domainSlug)->value('id');
            if (! $domainId) {
                continue;
            }

            foreach ($tiers as $tier) {
                $tierModel->newQuery()->firstOrCreate(
                    [
                        'knowledge_domain_id' => $domainId,
                        'name' => $tier['name'],
                    ],
                    $tier + ['knowledge_domain_id' => $domainId],
                );
            }
        }
    }

    private function tiers(): array
    {
        return [
            'reformer' => [
                ['name' => 'Foundation', 'min_points' => 0, 'badge_icon' => 'circle-dot', 'sort_order' => 1],
                ['name' => 'Troubleshooter', 'min_points' => 40, 'badge_icon' => 'wrench', 'sort_order' => 2],
                ['name' => 'Senior Reformer Expert', 'min_points' => 90, 'badge_icon' => 'award', 'sort_order' => 3],
            ],
            'co2-removal' => [
                ['name' => 'Solvent Basics', 'min_points' => 0, 'badge_icon' => 'droplet', 'sort_order' => 1],
                ['name' => 'Unit Specialist', 'min_points' => 25, 'badge_icon' => 'shield-check', 'sort_order' => 2],
                ['name' => 'CO2 Removal Expert', 'min_points' => 65, 'badge_icon' => 'medal', 'sort_order' => 3],
            ],
            'process-safety' => [
                ['name' => 'Safety Aware', 'min_points' => 0, 'badge_icon' => 'hard-hat', 'sort_order' => 1],
                ['name' => 'Safety Practitioner', 'min_points' => 20, 'badge_icon' => 'shield', 'sort_order' => 2],
            ],
        ];
    }
}

class DomainRankTierSeedModel extends Model
{
    protected $table = 'domain_rank_tiers';

    protected $guarded = [];
}