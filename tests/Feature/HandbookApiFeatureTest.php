<?php

namespace Tests\Feature;

use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\HandbookMetadata;
use App\Models\HandbookRelatedItem;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\MediaFile;
use App\Models\PlantType;
use App\Models\User;
use Database\Seeders\HandbookSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandbookApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_handbook_api_lists_published_categories_articles_tree_and_hotspots(): void
    {
        $category = HandbookCategory::factory()->published()->withHotspot()->create([
            'title' => 'Synthesis Loop',
            'slug' => 'synthesis-loop',
            'sort_order' => 1,
            'map_coordinates' => ['x' => 54, 'y' => 42, 'label' => 'Loop'],
        ]);
        $child = HandbookCategory::factory()->published()->create([
            'title' => 'Converter',
            'slug' => 'converter',
            'parent_id' => $category->id,
            'sort_order' => 5,
        ]);
        $draftCategory = HandbookCategory::factory()->create([
            'title' => 'Draft Utility',
            'slug' => 'draft-utility',
            'status' => 'draft',
        ]);
        $article = HandbookArticle::factory()->published()->withProcessData()->create([
            'category_id' => $category->id,
            'title' => 'Loop Pressure Monitoring',
            'slug' => 'loop-pressure-monitoring',
        ]);
        $draftArticle = HandbookArticle::factory()->create([
            'category_id' => $draftCategory->id,
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'status' => 'draft',
        ]);
        HandbookMetadata::query()->create([
            'article_id' => $article->id,
            'meta_type' => 'kpi',
            'meta_key' => 'pressure',
            'meta_value' => '10 bar',
            'vector_status' => 'pending',
        ]);

        $this->getJson('/api/v1/handbook/categories')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'title', 'slug', 'status', 'articles_count']]])
            ->assertJsonPath('data.0.slug', 'synthesis-loop')
            ->assertJsonMissing(['slug' => 'draft-utility']);

        $this->getJson('/api/v1/handbook/categories/tree')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'synthesis-loop')
            ->assertJsonPath('data.0.children.0.slug', $child->slug);

        $this->getJson('/api/v1/handbook/categories/hotspots')
            ->assertOk()
            ->assertJsonPath('data.0.map_coordinates.label', 'Loop');

        $this->getJson('/api/v1/handbook/articles?published=1&q=Pressure')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'category_id', 'title', 'slug', 'status', 'is_ai_trainable']]])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $article->id)
            ->assertJsonMissing(['id' => $draftArticle->id]);

        $this->getJson("/api/v1/handbook/articles/{$article->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'loop-pressure-monitoring')
            ->assertJsonStructure(['data' => ['content', 'category', 'metadata', 'related_items']]);
    }

    public function test_handbook_api_requires_auth_admin_policy_and_existing_resources_for_mutations(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->professional()->create();
        $article = HandbookArticle::factory()->create(['status' => 'draft']);
        $article->forceFill(['user_id' => null])->save();

        $this->postJson("/api/v1/handbook/articles/{$article->slug}/publish")
            ->assertUnauthorized();

        $this->actingAs($user)
            ->postJson("/api/v1/handbook/articles/{$article->slug}/publish")
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson("/api/v1/handbook/articles/{$article->slug}/publish", ['user_id' => $admin->id])
            ->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.user_id', $admin->id);

        $this->getJson('/api/v1/handbook/articles/missing-article')->assertNotFound();
        $this->getJson('/api/v1/handbook/categories/missing-category')->assertNotFound();
    }

    public function test_handbook_api_validates_filters_publish_payload_and_related_item_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        $article = HandbookArticle::factory()->published()->create();

        $this->getJson('/api/v1/handbook/categories?plant_type_id=999999&per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plant_type_id', 'per_page']);

        $this->getJson('/api/v1/handbook/articles?category_id=999999&plant_type_id=999999&status=bad&per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'plant_type_id', 'status', 'per_page']);

        $this->actingAs($admin)
            ->postJson("/api/v1/handbook/articles/{$article->slug}/publish", ['user_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);

        $this->actingAs($admin)
            ->postJson("/api/v1/handbook/articles/{$article->slug}/related-items", [
                'relatable_type' => '',
                'relatable_id' => 0,
                'relation_type' => 'bad',
                'sort_order' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['relatable_type', 'relatable_id', 'relation_type', 'sort_order']);
    }

    public function test_admin_can_link_related_library_items_through_handbook_api(): void
    {
        $admin = User::factory()->admin()->create();
        $article = HandbookArticle::factory()->published()->create();
        $libraryItem = $this->createLibraryItem($this->createLibraryCategory());

        $this->actingAs($admin)
            ->postJson("/api/v1/handbook/articles/{$article->slug}/related-items", [
                'relatable_type' => LibraryItem::class,
                'relatable_id' => $libraryItem->id,
                'relation_type' => 'library_item',
                'sort_order' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('data.handbook_article_id', $article->id)
            ->assertJsonPath('data.relatable_id', $libraryItem->id)
            ->assertJsonPath('data.relation_type', 'library_item');

        $this->getJson("/api/v1/handbook/articles/{$article->slug}/related-items")
            ->assertOk()
            ->assertJsonPath('data.0.relatable_id', $libraryItem->id);
    }

    public function test_handbook_fk_constraints_cover_shared_dependencies(): void
    {
        $admin = User::factory()->admin()->create();
        $category = HandbookCategory::factory()->published()->create();
        $article = HandbookArticle::factory()->published()->create(['category_id' => $category->id]);

        try {
            HandbookCategory::query()->create([
                'title' => 'Broken Plant',
                'slug' => 'broken-plant',
                'plant_type_id' => 999999,
                'layout_image_media_id' => null,
                'sort_order' => 1,
                'status' => 'published',
            ]);
            $this->fail('Expected invalid plant type FK to fail.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        try {
            HandbookCategory::query()->create([
                'title' => 'Broken Media',
                'slug' => 'broken-media',
                'plant_type_id' => null,
                'layout_image_media_id' => 999999,
                'sort_order' => 1,
                'status' => 'published',
            ]);
            $this->fail('Expected invalid media FK to fail.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        try {
            HandbookArticle::factory()->create(['category_id' => 999999]);
            $this->fail('Expected invalid category FK to fail.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        try {
            $article->forceFill(['user_id' => 999999])->save();
            $this->fail('Expected invalid user FK to fail.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_handbook_seeded_demo_data_is_visible_in_public_api(): void
    {
        PlantType::query()->create(['name' => 'Ammonia', 'slug' => 'ammonia', 'is_active' => true, 'sort_order' => 10]);
        User::factory()->admin()->create();
        $this->createLibraryItem($this->createLibraryCategory(), ['item_type' => 'handbook']);

        $this->seed(HandbookSeeder::class);

        $this->getJson('/api/v1/handbook/articles?published=1&q=Start-up')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'startup-readiness-walkthrough');

        $this->getJson('/api/v1/handbook/categories/hotspots')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'ammonia-plant-overview');
    }

    private function createLibraryCategory(array $attributes = []): LibraryCategory
    {
        return LibraryCategory::query()->create(array_merge([
            'title' => 'Library Category',
            'slug' => 'library-category',
            'parent_id' => null,
            'sort_order' => 10,
        ], $attributes));
    }

    private function createLibraryItem(LibraryCategory $category, array $attributes = []): LibraryItem
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
}
