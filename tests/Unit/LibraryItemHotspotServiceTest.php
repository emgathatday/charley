<?php

namespace Tests\Unit;

use App\Models\KnowledgeDomain;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\LibraryItemHotspot;
use App\Services\Library\LibraryItemHotspotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LibraryItemHotspotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_updates_reorders_and_replaces_hotspots(): void
    {
        $item = $this->item();
        $domain = $this->domain();
        $service = new LibraryItemHotspotService;

        $hotspot = $service->create($item, $domain, ['label' => 'Pump', 'shape_type' => LibraryItemHotspot::SHAPE_RECT, 'coordinates' => ['x' => 1, 'y' => 2], 'sort_order' => 5]);
        $updated = $service->update($hotspot, ['label' => 'Updated', 'coordinates' => ['x' => 3], 'sort_order' => 2]);
        $service->replaceForLibraryItem($item, [['knowledge_domain_id' => $domain->id, 'label' => 'New', 'shape_type' => LibraryItemHotspot::SHAPE_CIRCLE, 'coordinates' => ['cx' => 5], 'sort_order' => 1]]);

        $this->assertSame('Updated', $updated->label);
        $this->assertDatabaseMissing('library_item_hotspots', ['id' => $hotspot->id]);
        $this->assertDatabaseHas('library_item_hotspots', ['library_item_id' => $item->id, 'knowledge_domain_id' => $domain->id, 'label' => 'New']);
    }

    public function test_rejects_invalid_shape_empty_coordinates_and_missing_domain_reference(): void
    {
        $service = new LibraryItemHotspotService;
        $item = $this->item();
        $domain = $this->domain();

        foreach ([
            fn () => $service->create($item, $domain, ['shape_type' => 'bad', 'coordinates' => ['x' => 1]]),
            fn () => $service->create($item, $domain, ['shape_type' => LibraryItemHotspot::SHAPE_RECT, 'coordinates' => []]),
            fn () => $service->replaceForLibraryItem($item, [['label' => 'Missing domain', 'coordinates' => ['x' => 1]]]),
        ] as $callback) {
            try {
                $callback();
                $this->fail('Expected invalid hotspot exception.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    private function domain(): KnowledgeDomain
    {
        return KnowledgeDomain::query()->create(['name' => 'Process', 'slug' => 'process', 'status' => KnowledgeDomain::STATUS_ACTIVE]);
    }

    private function item(): LibraryItem
    {
        $category = LibraryCategory::query()->create(['title' => 'Category', 'slug' => 'category-'.uniqid(), 'sort_order' => 1]);
        return LibraryItem::query()->create(['category_id' => $category->id, 'title' => 'Library Item', 'slug' => 'library-item-'.uniqid(), 'content' => 'Body', 'access_level' => 'public', 'download_allowed' => false, 'copy_paste_disabled' => false, 'status' => 'published', 'is_ai_trainable' => true, 'content_type' => 'article', 'item_type' => 'article', 'download_count' => 0, 'view_count' => 0]);
    }
}
