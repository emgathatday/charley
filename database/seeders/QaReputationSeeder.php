<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QaReputationSeeder extends Seeder
{
    public function run(): void
    {
        $rankTiers = [
            ['star_level' => 1, 'min_points' => 0, 'label' => 'Star 1'],
            ['star_level' => 2, 'min_points' => 100, 'label' => 'Star 2'],
            ['star_level' => 3, 'min_points' => 500, 'label' => 'Star 3'],
            ['star_level' => 4, 'min_points' => 1000, 'label' => 'Star 4'],
            ['star_level' => 5, 'min_points' => 2500, 'label' => 'Star 5'],
        ];

        foreach ($rankTiers as $rankTier) {
            DB::table('reputation_rank_tiers')->updateOrInsert(
                ['star_level' => $rankTier['star_level']],
                [
                    'min_points' => $rankTier['min_points'],
                    'label' => $rankTier['label'],
                ],
            );
        }

        DB::table('leaderboard_settings')->updateOrInsert(
            ['effective_from' => '2026-01-01'],
            [
                'min_points_threshold' => 100,
                'top_n' => 10,
            ],
        );
    }
}
