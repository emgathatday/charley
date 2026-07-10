<?php

namespace Tests\Feature;

use App\Models\KnowledgeDomain;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\LibraryItemHotspot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryHotspotUiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_hotspot_page_and_admin_hotspot_menu_render(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->item();
        $domain = KnowledgeDomain::query()->create(['name' => 'Pump Operation', 'slug' => 'pump-operation', 'status' => KnowledgeDomain::STATUS_ACTIVE]);
        LibraryItemHotspot::query()->create(['library_item_id' => $item->id, 'knowledge_domain_id' => $domain->id, 'label' => 'Pump Area', 'shape_type' => 'rect', 'coordinates' => ['x' => 1], 'sort_order' => 1]);

        $this->get("/library/items/{$item->id}/hotspots")->assertOk()->assertSee('Pump Area');
        $this->get('/dashboard/library/hotspots')->assertRedirect('/login');
        $this->actingAs($admin)->get('/dashboard/library/hotspots')->assertOk()->assertSee('Hotspots')->assertSee('Pump Area');
    }

    public function test_admin_can_create_hotspot_and_validation_errors_are_returned(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->item();
        $domain = KnowledgeDomain::query()->create(['name' => 'Distillation', 'slug' => 'distillation', 'status' => KnowledgeDomain::STATUS_ACTIVE]);

        $this->actingAs($admin)->from('/dashboard/library/hotspots')->post('/dashboard/library/hotspots', [
            'library_item_id' => 999999, 'knowledge_domain_id' => 999999, 'shape_type' => 'bad', 'coordinates' => '',
        ])->assertRedirect('/dashboard/library/hotspots')->assertSessionHasErrors(['library_item_id', 'knowledge_domain_id', 'shape_type', 'coordinates']);

        $this->actingAs($admin)->post('/dashboard/library/hotspots', [
            'library_item_id' => $item->id, 'knowledge_domain_id' => $domain->id, 'label' => 'Column', 'shape_type' => 'circle', 'coordinates' => '{"cx":10,"cy":20,"r":5}', 'sort_order' => 1,
        ])->assertRedirect(route('admin.dashboard.library.hotspots.index'));

        $this->assertDatabaseHas('library_item_hotspots', ['library_item_id' => $item->id, 'knowledge_domain_id' => $domain->id, 'label' => 'Column']);
    }

    private function item(): LibraryItem
    {
        $category = LibraryCategory::query()->create(['title' => 'Category', 'slug' => 'category-'.uniqid(), 'sort_order' => 1]);
        return LibraryItem::query()->create(['category_id' => $category->id, 'title' => 'Diagram', 'slug' => 'diagram-'.uniqid(), 'content' => 'Body', 'access_level' => 'public', 'download_allowed' => false, 'copy_paste_disabled' => false, 'status' => 'published', 'is_ai_trainable' => true, 'content_type' => 'article', 'item_type' => 'article', 'download_count' => 0, 'view_count' => 0]);
    }
}
