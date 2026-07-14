<?php

namespace Database\Seeders;

use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\HandbookMetadata;
use App\Models\HandbookRelatedItem;
use App\Models\LibraryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HandbookSeeder extends Seeder
{
    public function run(): void
    {
        $plantTypeId = DB::table('plant_types')->where('is_active', true)->orderBy('sort_order')->value('id')
            ?? DB::table('plant_types')->value('id');
        $layoutImageMediaId = DB::table('media_files')->where('file_category', 'image')->value('id')
            ?? DB::table('media_files')->value('id');
        $userId = DB::table('users')->where('role', 'admin')->value('id')
            ?? DB::table('users')->value('id');

        $categories = collect($this->categories($plantTypeId, $layoutImageMediaId))->mapWithKeys(function (array $category): array {
            $record = HandbookCategory::query()->firstOrCreate(
                ['slug' => $category['slug']],
                $category,
            );

            return [$category['slug'] => $record];
        });

        foreach ($this->articles($categories, $userId) as $articleData) {
            $metadata = $articleData['metadata'];
            $relatedType = $articleData['related_type'];
            unset($articleData['metadata'], $articleData['related_type']);

            $article = HandbookArticle::query()->firstOrCreate(
                ['slug' => $articleData['slug']],
                $articleData,
            );

            foreach ($metadata as $meta) {
                HandbookMetadata::query()->firstOrCreate(
                    [
                        'article_id' => $article->id,
                        'meta_type' => $meta['meta_type'],
                        'meta_key' => $meta['meta_key'],
                    ],
                    $meta + [
                        'article_id' => $article->id,
                        'vector_status' => 'pending',
                    ],
                );
            }

            $libraryItemId = DB::table('library_items')->where('item_type', $relatedType)->orderBy('id')->value('id')
                ?? DB::table('library_items')->orderBy('id')->value('id');

            if ($libraryItemId) {
                HandbookRelatedItem::query()->firstOrCreate(
                    [
                        'handbook_article_id' => $article->id,
                        'relatable_type' => LibraryItem::class,
                        'relatable_id' => $libraryItemId,
                    ],
                    [
                        'relation_type' => 'library_item',
                        'sort_order' => 10,
                    ],
                );
            }
        }
    }

    private function categories(?int $plantTypeId, ?int $layoutImageMediaId): array
    {
        return [
            [
                'title' => 'Ammonia Plant Overview',
                'slug' => 'ammonia-plant-overview',
                'plant_type_id' => $plantTypeId,
                'parent_id' => null,
                'layout_image_media_id' => $layoutImageMediaId,
                'map_coordinates' => ['x' => 12, 'y' => 18, 'label' => 'Plant Overview'],
                'sort_order' => 10,
                'status' => 'published',
            ],
            [
                'title' => 'Synthesis Loop',
                'slug' => 'synthesis-loop',
                'plant_type_id' => $plantTypeId,
                'parent_id' => null,
                'layout_image_media_id' => $layoutImageMediaId,
                'map_coordinates' => ['x' => 54, 'y' => 42, 'label' => 'Synthesis Loop'],
                'sort_order' => 20,
                'status' => 'published',
            ],
            [
                'title' => 'Utility Systems',
                'slug' => 'utility-systems',
                'plant_type_id' => $plantTypeId,
                'parent_id' => null,
                'layout_image_media_id' => $layoutImageMediaId,
                'map_coordinates' => ['x' => 78, 'y' => 64, 'label' => 'Utilities'],
                'sort_order' => 30,
                'status' => 'draft',
            ],
        ];
    }

    private function articles($categories, ?int $userId): array
    {
        return [
            [
                'category_id' => $categories['ammonia-plant-overview']->id,
                'user_id' => $userId,
                'title' => 'Start-up Readiness Walkthrough',
                'slug' => 'startup-readiness-walkthrough',
                'summary' => 'Demo handbook article for start-up readiness checks and operating handover.',
                'content' => 'Use this demo handbook entry to validate navigation, metadata, and AI-training flows before approved operating procedures are published.',
                'optimization_guidance' => 'Review interlocks, permits, critical alarms, and shift handover notes before releasing the unit to operation.',
                'failure_modes' => [
                    ['mode' => 'Incomplete permit review', 'mitigation' => 'Confirm permit sign-off before equipment energization.'],
                    ['mode' => 'Unstable feed condition', 'mitigation' => 'Hold ramp rate until process indicators stabilize.'],
                ],
                'status' => 'published',
                'is_ai_trainable' => true,
                'ai_shortcut_config' => ['enabled' => true, 'prompt_key' => 'startup-checklist'],
                'view_count' => 18,
                'process_description' => 'A controlled readiness sequence covering personnel, process safety, equipment status, and operating limits.',
                'related_type' => 'handbook',
                'metadata' => [
                    ['meta_type' => 'kpi', 'meta_key' => 'readiness_score', 'meta_value' => 'Target readiness score above 95%.'],
                    ['meta_type' => 'iow', 'meta_key' => 'startup_ramp_rate', 'meta_value' => 'Keep ramp rate within approved operating window.'],
                ],
            ],
            [
                'category_id' => $categories['synthesis-loop']->id,
                'user_id' => $userId,
                'title' => 'Synthesis Loop Pressure Monitoring',
                'slug' => 'synthesis-loop-pressure-monitoring',
                'summary' => 'Demo handbook article for pressure-window monitoring and troubleshooting.',
                'content' => 'This article demonstrates KPI and troubleshooting metadata attached to a published handbook article.',
                'optimization_guidance' => 'Trend loop pressure against converter temperature and recycle compressor load.',
                'failure_modes' => [
                    ['mode' => 'Pressure drift', 'mitigation' => 'Check recycle compressor performance and downstream restriction indicators.'],
                ],
                'status' => 'published',
                'is_ai_trainable' => true,
                'ai_shortcut_config' => ['enabled' => true, 'prompt_key' => 'pressure-troubleshooting'],
                'view_count' => 11,
                'process_description' => 'Pressure monitoring supports early detection of converter or recycle loop instability.',
                'related_type' => 'article',
                'metadata' => [
                    ['meta_type' => 'troubleshooting', 'meta_key' => 'pressure_drift', 'meta_value' => 'Compare loop pressure, recycle flow, and converter temperature trends.'],
                    ['meta_type' => 'equipment_spec', 'meta_key' => 'recycle_compressor', 'meta_value' => 'Validate compressor operating envelope before recommending corrective action.'],
                ],
            ],
        ];
    }
}
