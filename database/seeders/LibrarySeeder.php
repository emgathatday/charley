<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('role', 'admin')->value('id')
            ?? DB::table('users')->value('id');
        $sampleUserId = DB::table('users')->orderBy('id')->value('id');
        $plantTypeId = DB::table('plant_types')->where('is_active', true)->orderBy('sort_order')->value('id')
            ?? DB::table('plant_types')->value('id');
        $mediaFileId = DB::table('media_files')->where('file_category', 'document')->value('id')
            ?? DB::table('media_files')->value('id');

        $categoryModel = new LibraryCategorySeedModel();
        $categories = collect($this->categories())->mapWithKeys(function (array $category) use ($categoryModel) {
            $record = $categoryModel->newQuery()->firstOrCreate(
                ['slug' => $category['slug']],
                $category,
            );

            return [$category['slug'] => $record];
        });

        $itemModel = new LibraryItemSeedModel();
        foreach ($this->items($categories, $adminUserId, $sampleUserId, $plantTypeId, $mediaFileId) as $item) {
            $itemModel->newQuery()->firstOrCreate(
                ['slug' => $item['slug']],
                $item,
            );
        }

        $ruleModel = new LibraryAccessRuleSeedModel();
        foreach ($this->accessRules($adminUserId) as $rule) {
            $ruleModel->newQuery()->firstOrCreate(
                ['partner_tier' => $rule['partner_tier']],
                $rule,
            );
        }

        $firstItemId = DB::table('library_items')->orderBy('id')->value('id');
        if ($firstItemId && $sampleUserId) {
            $logModel = new LibraryAccessLogSeedModel();
            foreach (['view', 'download'] as $action) {
                $logModel->newQuery()->firstOrCreate(
                    [
                        'library_item_id' => $firstItemId,
                        'user_id' => $sampleUserId,
                        'action' => $action,
                    ],
                    [
                        'ip_address' => '127.0.0.1',
                        'created_at' => now()->subMinutes($action === 'view' ? 30 : 10),
                    ],
                );
            }
        }
    }

    private function categories(): array
    {
        return [
            ['title' => 'Process Safety', 'slug' => 'process-safety', 'parent_id' => null, 'sort_order' => 10],
            ['title' => 'Operations Handbook', 'slug' => 'operations-handbook', 'parent_id' => null, 'sort_order' => 20],
            ['title' => 'Case Studies', 'slug' => 'case-studies', 'parent_id' => null, 'sort_order' => 30],
        ];
    }

    private function items($categories, ?int $adminUserId, ?int $sampleUserId, ?int $plantTypeId, ?int $mediaFileId): array
    {
        return [
            [
                'category_id' => $categories['process-safety']->id,
                'user_id' => $sampleUserId,
                'title' => 'Safe Start-up Checklist for Process Units',
                'slug' => 'safe-start-up-checklist-process-units',
                'summary' => 'Demo technical checklist for reviewing start-up readiness, interlocks, permits and operating limits.',
                'content' => 'This safe demo item outlines readiness checks, shift handover notes and verification steps for process unit start-up. Replace with approved technical content before production use.',
                'plant_type_id' => $plantTypeId,
                'author' => 'Charley Technical Team',
                'source' => 'Demo Library',
                'published_year' => 2026,
                'access_level' => 'professional_only',
                'download_allowed' => true,
                'copy_paste_disabled' => true,
                'download_count' => 0,
                'status' => 'published',
                'is_ai_trainable' => true,
                'content_type' => 'document',
                'item_type' => 'handbook',
                'view_count' => 12,
                'approved_by' => $adminUserId,
                'approved_at' => now(),
                'year' => 2026,
                'file_media_id' => $mediaFileId,
            ],
            [
                'category_id' => $categories['operations-handbook']->id,
                'user_id' => $sampleUserId,
                'title' => 'Operator Shift Handover Template',
                'slug' => 'operator-shift-handover-template',
                'summary' => 'Demo operations template for consistent shift handover notes and open action tracking.',
                'content' => 'This approved demo content captures equipment status, process deviations, safety notes and pending maintenance actions.',
                'plant_type_id' => $plantTypeId,
                'author' => 'Charley Operations Team',
                'source' => 'Demo Library',
                'published_year' => 2026,
                'access_level' => 'member',
                'download_allowed' => false,
                'copy_paste_disabled' => false,
                'download_count' => 0,
                'status' => 'published',
                'is_ai_trainable' => true,
                'content_type' => 'article',
                'item_type' => 'article',
                'view_count' => 8,
                'approved_by' => $adminUserId,
                'approved_at' => now(),
                'year' => 2026,
                'file_media_id' => null,
            ],
        ];
    }

    private function accessRules(?int $adminUserId): array
    {
        return [
            ['partner_tier' => 'gold', 'can_view' => true, 'can_download' => false, 'can_copy_paste' => false, 'requires_watermark' => true, 'max_downloads_per_month' => 10, 'notes' => 'Gold tier can view approved professional library content.', 'updated_by' => $adminUserId],
            ['partner_tier' => 'diamond', 'can_view' => true, 'can_download' => true, 'can_copy_paste' => false, 'requires_watermark' => true, 'max_downloads_per_month' => 40, 'notes' => 'Diamond tier can download watermarked approved documents.', 'updated_by' => $adminUserId],
            ['partner_tier' => 'platinum', 'can_view' => true, 'can_download' => true, 'can_copy_paste' => true, 'requires_watermark' => false, 'max_downloads_per_month' => null, 'notes' => 'Platinum tier has expanded access subject to approval rules.', 'updated_by' => $adminUserId],
        ];
    }
}

class LibraryCategorySeedModel extends Model
{
    protected $table = 'library_categories';

    protected $guarded = [];
}

class LibraryItemSeedModel extends Model
{
    protected $table = 'library_items';

    protected $guarded = [];
}

class LibraryAccessRuleSeedModel extends Model
{
    protected $table = 'library_access_rules';

    protected $guarded = [];
}

class LibraryAccessLogSeedModel extends Model
{
    public $timestamps = false;

    protected $table = 'library_access_logs';

    protected $guarded = [];
}
