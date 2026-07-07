<?php

namespace Tests\Feature;

use App\Models\LibraryAccessRule;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\MediaFile;
use App\Models\PlantType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LibraryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_schema_contains_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('library_categories', ['id', 'title', 'slug', 'parent_id', 'sort_order']));
        $this->assertTrue(Schema::hasColumns('library_items', [
            'id',
            'category_id',
            'user_id',
            'title',
            'slug',
            'summary',
            'content',
            'plant_type_id',
            'access_level',
            'download_allowed',
            'copy_paste_disabled',
            'status',
            'is_ai_trainable',
            'content_type',
            'approved_by',
            'approved_at',
            'file_media_id',
        ]));
        $this->assertTrue(Schema::hasColumns('library_access_logs', ['id', 'library_item_id', 'user_id', 'action', 'ip_address', 'created_at']));
        $this->assertTrue(Schema::hasColumns('library_access_rules', [
            'id',
            'partner_tier',
            'can_view',
            'can_download',
            'can_copy_paste',
            'requires_watermark',
            'max_downloads_per_month',
            'updated_by',
        ]));
    }

    public function test_public_library_api_lists_categories_items_and_hides_unpublished(): void
    {
        $category = $this->createCategory(['title' => 'Safety Library', 'slug' => 'safety-library']);
        $plantType = PlantType::query()->create(['name' => 'Ammonia', 'slug' => 'ammonia', 'is_active' => true, 'sort_order' => 10]);
        $published = $this->createItem($category, [
            'title' => 'Pressure Safety Handbook',
            'slug' => 'pressure-safety-handbook',
            'plant_type_id' => $plantType->id,
            'content_type' => 'document',
        ]);
        $draft = $this->createItem($category, [
            'title' => 'Draft Handbook',
            'slug' => 'draft-handbook',
            'status' => LibraryItem::STATUS_DRAFT,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $this->getJson('/api/v1/library/categories')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Safety Library');

        $this->getJson('/api/v1/library/items?search=Pressure&content_type=document')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'title', 'slug', 'access_level', 'content_type', 'file_media_id']]])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id);

        $this->getJson("/api/v1/library/items/{$published->id}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'pressure-safety-handbook');

        $this->getJson("/api/v1/library/items/{$draft->id}")->assertNotFound();
    }

    public function test_library_admin_api_requires_admin_and_validates_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $category = $this->createCategory();

        $this->getJson('/api/v1/library/admin/items')->assertUnauthorized();

        $this->actingAs($user)
            ->postJson('/api/v1/library/admin/items', ['title' => 'Denied'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson('/api/v1/library/admin/items', [
                'category_id' => 999999,
                'title' => '',
                'content_type' => 'bad',
                'access_level' => 'bad',
                'status' => 'bad',
                'download_allowed' => 'bad',
                'file_media_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'title', 'content_type', 'access_level', 'status', 'download_allowed', 'file_media_id']);

        $itemId = $this->actingAs($admin)
            ->postJson('/api/v1/library/admin/items', [
                'category_id' => $category->id,
                'title' => 'Pump Maintenance Guide',
                'summary' => null,
                'content' => 'Guide content',
                'access_level' => 'professional_only',
                'download_allowed' => false,
                'copy_paste_disabled' => true,
                'content_type' => 'article',
                'item_type' => 'article',
                'is_ai_trainable' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'pump-maintenance-guide')
            ->json('data.id');

        $this->actingAs($admin)
            ->putJson("/api/v1/library/admin/items/{$itemId}", ['title' => 'Pump Maintenance Guide Updated'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'pump-maintenance-guide-updated');

        $this->actingAs($admin)
            ->postJson("/api/v1/library/admin/items/{$itemId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', LibraryItem::STATUS_PUBLISHED)
            ->assertJsonPath('data.approved_by', $admin->id);

        $this->actingAs($admin)
            ->postJson("/api/v1/library/admin/items/{$itemId}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', LibraryItem::STATUS_ARCHIVED);
    }

    public function test_access_rules_tier_download_disabled_and_watermark_requirements_are_enforced(): void
    {
        $partner = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $professional = User::factory()->professional()->create();
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $media = $this->createMediaFile($admin);
        $item = $this->createItem($category, [
            'access_level' => 'partner_only',
            'download_allowed' => true,
            'copy_paste_disabled' => false,
            'file_media_id' => $media->id,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/v1/library/access-rules', [
                'partner_tier' => 'gold',
                'can_view' => true,
                'can_download' => false,
                'can_copy_paste' => true,
                'requires_watermark' => true,
                'max_downloads_per_month' => 3,
            ])
            ->assertCreated()
            ->assertJsonPath('data.updated_by', $admin->id);

        $this->actingAs($professional)
            ->postJson("/api/v1/library/items/{$item->id}/access-check", ['partner_tier' => 'gold'])
            ->assertOk();

        $this->actingAs($partner)
            ->postJson("/api/v1/library/items/{$item->id}/access-check", ['partner_tier' => 'gold'])
            ->assertOk()
            ->assertJsonPath('data.can_view', true)
            ->assertJsonPath('data.can_download', false)
            ->assertJsonPath('data.can_copy_paste', true)
            ->assertJsonPath('data.requires_watermark', true);

        $this->actingAs($partner)
            ->postJson("/api/v1/library/items/{$item->id}/access-logs", ['action' => 'download', 'partner_tier' => 'gold'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['library_item']);

        $this->actingAs($admin)
            ->putJson('/api/v1/library/access-rules/1', [
                'partner_tier' => 'gold',
                'can_view' => true,
                'can_download' => true,
                'can_copy_paste' => true,
                'requires_watermark' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.can_download', true);

        $this->actingAs($partner)
            ->postJson("/api/v1/library/items/{$item->id}/access-logs", ['action' => 'download', 'partner_tier' => 'gold'])
            ->assertCreated()
            ->assertJsonPath('data.action', 'download');

        $this->assertDatabaseHas('library_items', ['id' => $item->id, 'download_count' => 1]);
    }

    public function test_admin_and_public_library_web_routes_render_access_states(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $category = $this->createCategory(['title' => 'Operations', 'slug' => 'operations']);
        $item = $this->createItem($category, [
            'title' => 'Operations Handbook',
            'slug' => 'operations-handbook',
            'access_level' => 'member',
        ]);
        $adminOnly = $this->createItem($category, [
            'title' => 'Admin Handbook',
            'slug' => 'admin-handbook',
            'access_level' => 'admin_only',
        ]);

        $this->get('/library')->assertOk()->assertSee('Operations Handbook');
        $this->get("/library/categories/{$category->id}")->assertOk()->assertSee('Operations');
        $this->get("/library/items/{$item->id}")->assertOk()->assertSee('This library item is protected');

        $this->actingAs($user)
            ->get("/library/items/{$item->id}")
            ->assertOk()
            ->assertSee('Operations Handbook');

        $this->actingAs($user)
            ->get("/library/items/{$adminOnly->id}/preview")
            ->assertOk()
            ->assertSee('Preview is not available for this access level.');

        $this->get('/dashboard/library')->assertForbidden();
        $this->actingAs($user)->get('/dashboard/library')->assertForbidden();
        $this->actingAs($admin)
            ->get('/dashboard/library')
            ->assertOk()
            ->assertSee('Library');
    }

    public function test_fk_constraints_fail_for_invalid_library_records(): void
    {
        $this->expectException(QueryException::class);
        LibraryItem::query()->create([
            'category_id' => 999999,
            'title' => 'Broken Library Item',
            'slug' => 'broken-library-item',
            'access_level' => 'public',
            'download_allowed' => false,
            'copy_paste_disabled' => false,
            'status' => LibraryItem::STATUS_DRAFT,
            'is_ai_trainable' => true,
            'content_type' => 'article',
        ]);
    }

    private function createCategory(array $attributes = []): LibraryCategory
    {
        return LibraryCategory::query()->create(array_merge([
            'title' => 'Library Category',
            'slug' => 'library-category',
            'parent_id' => null,
            'sort_order' => 10,
        ], $attributes));
    }

    private function createItem(LibraryCategory $category, array $attributes = []): LibraryItem
    {
        $approver = User::factory()->admin()->create();

        return LibraryItem::query()->create(array_merge([
            'category_id' => $category->id,
            'user_id' => $approver->id,
            'title' => 'Library Item',
            'slug' => 'library-item',
            'summary' => 'Library summary',
            'content' => 'Library content',
            'plant_type_id' => null,
            'author' => 'Admin',
            'source' => 'Internal',
            'published_year' => 2026,
            'access_level' => 'public',
            'download_allowed' => false,
            'copy_paste_disabled' => false,
            'download_count' => 0,
            'status' => LibraryItem::STATUS_PUBLISHED,
            'is_ai_trainable' => true,
            'content_type' => 'article',
            'item_type' => 'article',
            'view_count' => 0,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'year' => 2026,
            'file_media_id' => null,
        ], $attributes));
    }

    private function createMediaFile(User $user): MediaFile
    {
        return MediaFile::query()->create([
            'uploader_id' => $user->id,
            'disk' => 'local',
            'path' => 'library/guide.pdf',
            'original_name' => 'guide.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'upload_context' => 'library_item',
            'file_category' => 'document',
            'processing_status' => 'processed',
            'is_orphan' => false,
        ]);
    }
}
